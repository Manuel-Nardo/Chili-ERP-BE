<?php

namespace App\Services;

use App\Models\PedidoSugerencia;
use App\Models\PedidoSugerenciaDetalle;
use App\Models\Producto;
use App\Services\Forecast\ForecastHistoricoService;
use App\Services\Forecast\ForecastMotorService;
use App\Models\PedidoErp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PedidoSugerenciaService
{
    public function __construct(
        protected ForecastHistoricoService $forecastHistoricoService,
        protected ForecastMotorService $forecastMotorService,
    ) {}

    public function create(array $data, ?int $userId = null): PedidoSugerencia
    {
        return DB::transaction(function () use ($data, $userId) {
            $detalles = $data['detalles'] ?? [];

            $this->validarProductosContraTipoPedido(
                (int) $data['tipo_pedido_id'],
                $detalles
            );

            $sugerencia = PedidoSugerencia::create([
                'cliente_id' => (int) $data['cliente_id'],
                'tipo_pedido_id' => (int) $data['tipo_pedido_id'],
                'fecha_objetivo' => Carbon::parse($data['fecha_objetivo'])->format('Y-m-d'),
                'estatus' => PedidoSugerencia::ESTATUS_BORRADOR,
                'origen' => $data['origen'],
                'modelo' => $data['modelo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->syncDetalles($sugerencia, $detalles);

            return $this->loadRelations($sugerencia);
        });
    }

    public function update(PedidoSugerencia $sugerencia, array $data, ?int $userId = null): PedidoSugerencia
    {
        if (! $sugerencia->esEditable()) {
            throw new RuntimeException('La sugerencia solo puede editarse cuando está en borrador.');
        }

        return DB::transaction(function () use ($sugerencia, $data, $userId) {
            $detalles = $data['detalles'] ?? [];

            $this->validarProductosContraTipoPedido(
                (int) $data['tipo_pedido_id'],
                $detalles
            );

            $sugerencia->update([
                'cliente_id' => (int) $data['cliente_id'],
                'tipo_pedido_id' => (int) $data['tipo_pedido_id'],
                'fecha_objetivo' => Carbon::parse($data['fecha_objetivo'])->format('Y-m-d'),
                'origen' => $data['origen'],
                'modelo' => $data['modelo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'updated_by' => $userId,
            ]);

            $this->syncDetalles($sugerencia, $detalles);

            return $this->loadRelations($sugerencia);
        });
    }

    public function confirm(PedidoSugerencia $sugerencia, ?int $userId = null): PedidoSugerencia
    {
        if (! $sugerencia->esEditable()) {
            throw new RuntimeException('Solo se puede confirmar una sugerencia en borrador.');
        }

        if (! $sugerencia->detalles()->exists()) {
            throw new RuntimeException('No se puede confirmar una sugerencia sin productos.');
        }

        $sugerencia->update([
            'estatus' => PedidoSugerencia::ESTATUS_CONFIRMADO,
            'updated_by' => $userId,
        ]);

        return $this->loadRelations($sugerencia);
    }

    public function cancel(PedidoSugerencia $sugerencia, ?int $userId = null): PedidoSugerencia
    {
        if ($sugerencia->estaProcesado()) {
            throw new RuntimeException('No se puede cancelar una sugerencia ya procesada.');
        }

        if ($sugerencia->estaCancelado()) {
            throw new RuntimeException('La sugerencia ya se encuentra cancelada.');
        }

        $sugerencia->update([
            'estatus' => PedidoSugerencia::ESTATUS_CANCELADO,
            'updated_by' => $userId,
        ]);

        return $this->loadRelations($sugerencia);
    }

    public function generarForecast(array $data, ?int $userId = null): PedidoSugerencia
    {
        $clienteId = (int) $data['cliente_id'];
        $tipoPedidoId = (int) $data['tipo_pedido_id'];
        $fechaObjetivo = Carbon::parse($data['fecha_objetivo'])->format('Y-m-d');
        $diasHistorico = max(1, (int) ($data['dias_historico'] ?? 84));
        $forzarRegeneracion = filter_var($data['forzar_regeneracion'] ?? false, FILTER_VALIDATE_BOOL);
        $observaciones = $data['observaciones'] ?? null;

        $existente = PedidoSugerencia::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo_pedido_id', $tipoPedidoId)
            ->whereDate('fecha_objetivo', $fechaObjetivo)
            ->first();

        if ($existente && ! $forzarRegeneracion) {
            throw new RuntimeException('Ya existe una sugerencia para ese cliente, tipo de pedido y fecha objetivo.');
        }

        if ($existente && ! $existente->esEditable()) {
            throw new RuntimeException('La sugerencia existente no se puede regenerar porque ya no está en borrador.');
        }

        $productos = $this->forecastHistoricoService
            ->obtenerProductosContexto(
                clienteId: $clienteId,
                tipoPedidoId: $tipoPedidoId,
            )
            ->map(fn (array $producto) => [
                'producto_id' => (int) $producto['producto_id'],
                'producto_nombre' => $producto['producto_nombre'],
                'producto_fuente_id_principal' => $producto['producto_fuente_id_principal'] ?? null,
                'producto_fuente_clave_principal' => $producto['producto_fuente_clave_principal'] ?? null,
            ])
            ->values();

        if ($productos->isEmpty()) {
            throw new RuntimeException(
                'No hay equivalencias de productos activas para el cliente y tipo de pedido seleccionados.'
            );
        }

        $fechaFinHistorico = Carbon::parse($fechaObjetivo)->subDay()->format('Y-m-d');
        $fechaInicioHistorico = Carbon::parse($fechaFinHistorico)
            ->subDays($diasHistorico - 1)
            ->format('Y-m-d');

        $historico = $this->forecastHistoricoService->obtenerHistoricoVentas(
            clienteId: $clienteId,
            tipoPedidoId: $tipoPedidoId,
            fechaInicio: $fechaInicioHistorico,
            fechaFin: $fechaFinHistorico,
        );

        $detalles = $productos
            ->map(function (array $producto) use ($historico, $fechaObjetivo, $clienteId, $tipoPedidoId, $diasHistorico) {
                $historicoProducto = $historico
                    ->where('producto_id', $producto['producto_id'])
                    ->values();

                $forecast = $this->forecastMotorService->calcularForecastProducto(
                    producto: $producto,
                    historicoProducto: $historicoProducto,
                    fechaObjetivo: $fechaObjetivo,
                );

                $sugeridoFinal = (float) ($forecast['sugerido_final'] ?? 0);

                return [
                    'producto_id' => $producto['producto_id'],
                    'cantidad_sugerida' => $sugeridoFinal,
                    'cantidad_ajustada' => $sugeridoFinal,
                    'cantidad_final' => $sugeridoFinal,
                    'observaciones' => null,
                    'metadata' => [
                        'metricas' => $forecast['metricas'] ?? null,
                        'contexto_forecast' => [
                            'cliente_id' => $clienteId,
                            'tipo_pedido_id' => $tipoPedidoId,
                            'fecha_inicio_historico' => $historicoProducto->min('fecha'),
                            'fecha_fin_historico' => $historicoProducto->max('fecha'),
                            'dias_historico_solicitados' => $diasHistorico,
                            'dias_historico_utilizados' => $historicoProducto->count(),
                            'producto_fuente_id_principal' => $producto['producto_fuente_id_principal'] ?? null,
                            'producto_fuente_clave_principal' => $producto['producto_fuente_clave_principal'] ?? null,
                        ],
                    ],
                ];
            })
            ->values()
            ->all();

        if (empty($detalles)) {
            throw new RuntimeException('No fue posible generar detalles para la sugerencia con el histórico disponible.');
        }

        $payload = [
            'cliente_id' => $clienteId,
            'tipo_pedido_id' => $tipoPedidoId,
            'fecha_objetivo' => $fechaObjetivo,
            'origen' => defined(PedidoSugerencia::class . '::ORIGEN_FORECAST')
                ? PedidoSugerencia::ORIGEN_FORECAST
                : 'forecast',
            'modelo' => 'forecast_v1',
            'observaciones' => $observaciones,
            'detalles' => $detalles,
        ];

        return DB::transaction(function () use ($existente, $payload, $userId) {
            if ($existente) {
                $existente->update([
                    'cliente_id' => $payload['cliente_id'],
                    'tipo_pedido_id' => $payload['tipo_pedido_id'],
                    'fecha_objetivo' => $payload['fecha_objetivo'],
                    'origen' => $payload['origen'],
                    'modelo' => $payload['modelo'],
                    'observaciones' => $payload['observaciones'],
                    'updated_by' => $userId,
                ]);

                $this->syncDetalles($existente, $payload['detalles']);

                return $this->loadRelations($existente);
            }

            return $this->create($payload, $userId);
        });
    }

    protected function syncDetalles(PedidoSugerencia $sugerencia, array $detalles): void
    {
        $sugerencia->detalles()->delete();

        foreach ($detalles as $detalle) {
            $cantidadSugerida = (float) ($detalle['cantidad_sugerida'] ?? 0);
            $cantidadAjustada = (float) ($detalle['cantidad_ajustada'] ?? $cantidadSugerida);
            $cantidadFinal = array_key_exists('cantidad_final', $detalle)
                ? (float) $detalle['cantidad_final']
                : $cantidadAjustada;

            PedidoSugerenciaDetalle::create([
                'pedido_sugerencia_id' => $sugerencia->id,
                'producto_id' => (int) $detalle['producto_id'],
                'cantidad_sugerida' => $cantidadSugerida,
                'cantidad_ajustada' => $cantidadAjustada,
                'cantidad_final' => $cantidadFinal,
                'observaciones' => $detalle['observaciones'] ?? null,
                'metadata' => $detalle['metadata'] ?? null,
            ]);
        }
    }

    protected function validarProductosContraTipoPedido(int $tipoPedidoId, array $detalles): void
    {
        if (empty($detalles)) {
            throw new InvalidArgumentException('Debes enviar al menos un detalle.');
        }

        $productoIds = collect($detalles)
            ->pluck('producto_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($productoIds->isEmpty()) {
            throw new InvalidArgumentException('No se encontraron productos válidos en el detalle.');
        }

        $duplicados = $productoIds->duplicates();
        if ($duplicados->isNotEmpty()) {
            throw new InvalidArgumentException('No puedes repetir productos dentro de la misma sugerencia.');
        }

        $productosExistentes = Producto::query()
            ->whereIn('id', $productoIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $faltantes = array_values(array_diff($productoIds->all(), $productosExistentes));
        if (! empty($faltantes)) {
            throw new InvalidArgumentException('Uno o más productos enviados no existen en el catálogo.');
        }

        $productosInvalidos = Producto::query()
            ->whereIn('id', $productoIds)
            ->where(function ($query) use ($tipoPedidoId) {
                $query->where('tipo_pedido_id', '!=', $tipoPedidoId)
                    ->orWhere('activo', false);
            })
            ->pluck('id')
            ->all();

        if (! empty($productosInvalidos)) {
            throw new InvalidArgumentException(
                'Uno o más productos no pertenecen al tipo de pedido seleccionado o están inactivos.'
            );
        }
    }

    protected function loadRelations(PedidoSugerencia $sugerencia): PedidoSugerencia
    {
        return $sugerencia->load([
            'cliente',
            'tipoPedido',
            'detalles.producto',
            'creador',
            'editor',
        ]);
    }

    public function generarPedidoDesdeSugerencia(int $id, ?int $userId = null): PedidoErp
    {
        return app(\App\Services\Pedidos\GenerarPedidoDesdeSugerenciaService::class)
            ->ejecutar($id);
    }
}