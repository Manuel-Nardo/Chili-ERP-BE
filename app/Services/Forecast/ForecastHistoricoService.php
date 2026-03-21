<?php

namespace App\Services\Forecast;

use App\Services\Integrations\PosApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ForecastHistoricoService
{
    public function __construct(
        protected PosApiService $posApiService,
    ) {}

    /**
     * Obtiene histórico transformado al mundo ERP.
     *
     * Retorna rows con:
     * - fecha
     * - cliente_id
     * - pos_sucursal_id
     * - tipo_pedido_id
     * - producto_id           (ERP producto_id)
     * - producto_fuente_id    (POS producto_id)
     * - ventas_dia
     * - producto_fuente_clave
     * - producto_nombre
     */
    public function obtenerHistoricoVentas(
        int $clienteId,
        int $tipoPedidoId,
        string $fechaInicio,
        string $fechaFin
    ): Collection {
        $fechaInicio = Carbon::parse($fechaInicio)->format('Y-m-d');
        $fechaFin = Carbon::parse($fechaFin)->format('Y-m-d');

        if ($fechaInicio > $fechaFin) {
            return collect();
        }

        $posSucursalId = $this->obtenerPosSucursalId($clienteId);

        $mapeos = $this->obtenerMapeosActivos(
            clienteId: $clienteId,
            tipoPedidoId: $tipoPedidoId,
        );

        if ($mapeos->isEmpty()) {
            return collect();
        }

        $productosFuente = $mapeos
            ->pluck('producto_fuente_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($productosFuente)) {
            return collect();
        }

        $fechaObjetivo = Carbon::parse($fechaFin)->addDay()->format('Y-m-d');
        $diasHistorico = Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin)) + 1;

        $historicoPos = collect($this->posApiService->obtenerHistoricoProductos([
            // IMPORTANTE:
            // aquí se manda el contexto POS dentro del parámetro que hoy usa tu integración.
            'cliente_id' => $posSucursalId,
            'clasificador_id' => $tipoPedidoId,
            'fecha_objetivo' => $fechaObjetivo,
            'dias_historico' => $diasHistorico,
            'productos' => $productosFuente,
        ]));

        if ($historicoPos->isEmpty()) {
            return collect();
        }

        $mapeoPorProductoFuente = $mapeos->keyBy(fn ($row) => (int) $row['producto_fuente_id']);
        $mapeoPorClaveFuente = $mapeos
            ->filter(fn ($row) => !empty($row['producto_fuente_clave']))
            ->keyBy(fn ($row) => mb_strtolower(trim((string) $row['producto_fuente_clave'])));

        return $historicoPos
            ->map(function ($row) use ($mapeoPorProductoFuente, $mapeoPorClaveFuente, $clienteId, $posSucursalId, $tipoPedidoId) {
                $productoFuenteId = (int) ($row['producto_id'] ?? 0);
                $productoFuenteClave = isset($row['producto_clave'])
                    ? mb_strtolower(trim((string) $row['producto_clave']))
                    : null;

                $mapeo = $mapeoPorProductoFuente->get($productoFuenteId);

                if (! $mapeo && $productoFuenteClave) {
                    $mapeo = $mapeoPorClaveFuente->get($productoFuenteClave);
                }

                if (! $mapeo) {
                    return null;
                }

                return [
                    'fecha' => $row['fecha'] ?? null,
                    'cliente_id' => $clienteId,
                    'pos_sucursal_id' => $posSucursalId,
                    'tipo_pedido_id' => $tipoPedidoId,
                    'producto_id' => (int) $mapeo['producto_id'],
                    'producto_fuente_id' => $productoFuenteId ?: null,
                    'ventas_dia' => (float) ($row['cantidad'] ?? 0),
                    'producto_fuente_clave' => $mapeo['producto_fuente_clave'] ?? null,
                    'producto_nombre' => $row['producto_nombre'] ?? null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Devuelve productos del contexto ERP junto con sus equivalencias POS.
     */
    public function obtenerProductosContexto(
        int $clienteId,
        int $tipoPedidoId
    ): Collection {
        $mapeos = $this->obtenerMapeosActivos(
            clienteId: $clienteId,
            tipoPedidoId: $tipoPedidoId,
        );

        if ($mapeos->isEmpty()) {
            return collect();
        }

        $productosErp = DB::table('productos')
            ->whereIn('id', $mapeos->pluck('producto_id')->unique()->values()->all())
            ->where('tipo_pedido_id', $tipoPedidoId)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
            ]);

        return collect($productosErp)
            ->map(function ($producto) use ($mapeos) {
                $productoId = (int) $producto->id;
                $grupo = $mapeos->where('producto_id', $productoId)->values();
                $primero = $grupo->first();

                return [
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->nombre,
                    'producto_fuente_ids' => $grupo
                        ->pluck('producto_fuente_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all(),
                    'producto_fuente_claves' => $grupo
                        ->pluck('producto_fuente_clave')
                        ->filter()
                        ->map(fn ($clave) => trim((string) $clave))
                        ->unique()
                        ->values()
                        ->all(),
                    'producto_fuente_id_principal' => isset($primero['producto_fuente_id'])
                        ? (int) $primero['producto_fuente_id']
                        : null,
                    'producto_fuente_clave_principal' => $primero['producto_fuente_clave'] ?? null,
                ];
            })
            ->values();
    }

    /**
     * Obtiene la sucursal POS principal activa asociada al cliente ERP.
     */
    protected function obtenerPosSucursalId(int $clienteId): int
    {
        $row = DB::table('cliente_sucursal_pos_mapeo')
            ->where('cliente_id', $clienteId)
            ->where('activo', 1)
            ->orderByDesc('es_principal')
            ->orderBy('id')
            ->first([
                'pos_sucursal_id',
            ]);

        if (! $row || empty($row->pos_sucursal_id)) {
            throw new RuntimeException('No existe una sucursal POS activa asociada al cliente seleccionado.');
        }

        return (int) $row->pos_sucursal_id;
    }

    /**
     * Equivalencias activas ERP <- POS por cliente y tipo de pedido.
     */
    protected function obtenerMapeosActivos(
        int $clienteId,
        int $tipoPedidoId
    ): Collection {
        return DB::table('forecast_producto_equivalencias')
            ->where('cliente_id', $clienteId)
            ->where('tipo_pedido_id', $tipoPedidoId)
            ->where('activo', 1)
            ->where(function ($query) {
                $query->whereNotNull('producto_fuente_id')
                    ->orWhereNotNull('producto_fuente_clave');
            })
            ->select([
                'cliente_id',
                'tipo_pedido_id',
                'producto_id',
                'producto_fuente_id',
                'producto_fuente_clave',
            ])
            ->orderBy('producto_id')
            ->get()
            ->map(function ($row) {
                return [
                    'cliente_id' => (int) $row->cliente_id,
                    'tipo_pedido_id' => (int) $row->tipo_pedido_id,
                    'producto_id' => (int) $row->producto_id,
                    'producto_fuente_id' => $row->producto_fuente_id !== null
                        ? (int) $row->producto_fuente_id
                        : null,
                    'producto_fuente_clave' => $row->producto_fuente_clave,
                ];
            })
            ->values();
    }
}