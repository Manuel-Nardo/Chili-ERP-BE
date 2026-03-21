<?php

namespace App\Services;

use App\Services\Integrations\PosApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PedidoSugerenciaForecastService
{
    public function __construct(
        protected PosApiService $posApiService,
    ) {}

    public function generarDesdePos(array $payload): array
    {
        $erpPedidoId = (int) $payload['erp_pedido_id'];
        $fechaObjetivo = Carbon::parse($payload['fecha_objetivo'])->format('Y-m-d');
        $diasHistorico = (int) ($payload['dias_historico'] ?? 56);
        $modelo = $payload['modelo'] ?? 'forecast_hibrido_v1';

        $mapeos = $this->obtenerMapeosActivos($erpPedidoId);

        if ($mapeos->isEmpty()) {
            throw new RuntimeException('No existen productos mapeados en erp_pedido_detalles para este pedido.');
        }

        $sucursalPosId = (int) $mapeos->pluck('cat_sucursal_id')->filter()->unique()->first();

        if (!$sucursalPosId) {
            throw new RuntimeException('No se encontró cat_sucursal_id válido en erp_pedido_detalles.');
        }

        $productosPos = $mapeos
            ->pluck('cat_producto_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $historicoPos = collect($this->posApiService->obtenerHistoricoProductos([
            'cliente_id' => $sucursalPosId,
            'clasificador_id' => $erpPedidoId,
            'fecha_objetivo' => $fechaObjetivo,
            'dias_historico' => $diasHistorico,
            'productos' => $productosPos,
        ]));

        $mapeoPorCat = $mapeos->keyBy(fn ($row) => (int) $row->cat_producto_id);

        $historicoErp = $historicoPos
            ->map(function ($row) use ($mapeoPorCat) {
                $catProductoId = (int) ($row['producto_id'] ?? 0);
                $map = $mapeoPorCat->get($catProductoId);

                if (!$map) {
                    return null;
                }

                return [
                    'erp_producto_id' => (int) $map->erp_producto_id,
                    'cat_producto_id' => $catProductoId,
                    'erp_clave' => $map->erp_clave,
                    'producto_nombre' => $row['producto_nombre'] ?? null,
                    'fecha' => $row['fecha'],
                    'cantidad' => (float) ($row['cantidad'] ?? 0),
                ];
            })
            ->filter()
            ->values();

        $historicoAgrupado = $historicoErp->groupBy('erp_producto_id');

        $detalles = $mapeos
            ->groupBy('erp_producto_id')
            ->map(function (Collection $grupo, $erpProductoId) use ($historicoAgrupado, $fechaObjetivo, $modelo) {
                $primerMapeo = $grupo->first();
                $rows = collect($historicoAgrupado->get((int) $erpProductoId, []));

                $forecast = $this->calcularForecastProducto($rows, $fechaObjetivo, $modelo);

                return [
                    'producto_id' => (int) $erpProductoId,
                    'cantidad_sugerida' => $forecast['cantidad_sugerida'],
                    'cantidad_ajustada' => $forecast['cantidad_sugerida'],
                    'cantidad_final' => $forecast['cantidad_sugerida'],
                    'observaciones' => null,
                    'meta' => [
                        'modelo' => $modelo,
                        'origen' => 'pos_api',
                        'cat_producto_ids' => $grupo->pluck('cat_producto_id')->map(fn ($v) => (int) $v)->values()->all(),
                        'erp_clave' => $primerMapeo->erp_clave,
                        ...$forecast['meta'],
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'cliente_id' => $sucursalPosId,
            'fecha_objetivo' => $fechaObjetivo,
            'dias_historico' => $diasHistorico,
            'detalles' => $detalles,
        ];
    }

    protected function obtenerMapeosActivos(int $erpPedidoId): Collection
    {
        return DB::table('erp_pedido_detalles')
            ->where('erp_pedido_id', $erpPedidoId)
            ->where('activo', 1)
            ->whereNotNull('erp_producto_id')
            ->whereNotNull('cat_producto_id')
            ->select([
                'erp_pedido_id',
                'erp_producto_id',
                'cat_producto_id',
                'erp_clave',
                'cat_sucursal_id',
            ])
            ->orderBy('erp_producto_id')
            ->get();
    }

    protected function calcularForecastProducto(Collection $rows, string $fechaObjetivo, string $modelo): array
    {
        $fecha = Carbon::parse($fechaObjetivo);
        $dayOfWeek = $fecha->dayOfWeek;

        $normalizado = $rows
            ->map(fn ($row) => [
                'fecha' => Carbon::parse($row['fecha']),
                'cantidad' => (float) $row['cantidad'],
            ])
            ->sortByDesc('fecha')
            ->values();

        if ($normalizado->isEmpty()) {
            return [
                'cantidad_sugerida' => 0,
                'meta' => [
                    'promedio_mismo_dia' => 0,
                    'promedio_reciente' => 0,
                    'promedio_general' => 0,
                    'factor_tendencia' => 1,
                    'muestras_mismo_dia' => 0,
                    'muestras_totales' => 0,
                ],
            ];
        }

        $mismoDia = $normalizado
            ->filter(fn ($row) => $row['fecha']->dayOfWeek === $dayOfWeek)
            ->take(4);

        $recientes = $normalizado->take(8);
        $anteriores = $normalizado->slice(8, 8);

        $promedioDia = round((float) $mismoDia->avg('cantidad'), 2);
        $promedioReciente = round((float) $recientes->avg('cantidad'), 2);
        $promedioGeneral = round((float) $normalizado->avg('cantidad'), 2);

        $avgRecientes = (float) $recientes->avg('cantidad');
        $avgAnteriores = (float) $anteriores->avg('cantidad');

        $factor = 1.0;
        if ($avgAnteriores > 0) {
            $factor = $avgRecientes / $avgAnteriores;
            $factor = max(0.85, min(1.15, $factor));
        }

        $base =
            ($promedioDia * 0.50) +
            ($promedioReciente * 0.30) +
            ($promedioGeneral * 0.20);

        $sugerido = round(max(0, $base * $factor), 2);

        return [
            'cantidad_sugerida' => $sugerido,
            'meta' => [
                'promedio_mismo_dia' => $promedioDia,
                'promedio_reciente' => $promedioReciente,
                'promedio_general' => $promedioGeneral,
                'factor_tendencia' => round($factor, 4),
                'muestras_mismo_dia' => $mismoDia->count(),
                'muestras_totales' => $normalizado->count(),
            ],
        ];
    }
}