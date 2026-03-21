<?php

namespace App\Services\Pedidos;

use App\Models\PedidoDetErp;
use App\Models\PedidoErp;
use App\Models\PedidoSugerencia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GenerarPedidoDesdeSugerenciaService
{
    public function ejecutar(int $pedidoSugerenciaId): PedidoErp
    {
        return DB::transaction(function () use ($pedidoSugerenciaId) {
            /** @var PedidoSugerencia $sugerencia */
            $sugerencia = PedidoSugerencia::query()
                ->with(['detalles'])
                ->lockForUpdate()
                ->findOrFail($pedidoSugerenciaId);

            $this->validarSugerencia($sugerencia);

            [$serieId, $folio] = $this->resolverSerieYFolio($sugerencia);

            $pedido = PedidoErp::query()->create([
                'serie' => $serieId,
                'num_folio' => $folio,
                'tipo' => $sugerencia->tipo_pedido_id,
                'estatus' => 'GENERADO',
                'fecha_pedido' => now()->toDateString(),
                'fecha_recepcion' => null,
                'fecha_objetivo' => $sugerencia->fecha_objetivo,
                'observaciones' => $sugerencia->observaciones ?? '',
                'subtotal' => 0,
                'impuestos' => 0,
                'total' => 0,
                'usuariorealizo' => $this->resolverUsuarioRealizo(),
                'sucursal' => $sugerencia->cliente_id, // si aquí realmente guardas sucursal destino
                'sucursal_origen' => null,
                'sucursal_destino' => $sugerencia->cliente_id, // ajustar si cliente_id !== sucursal_id
                'origen_tipo' => 'forecast',
                'origen_id' => $sugerencia->id,
                'pedido_sugerencia_id' => $sugerencia->id,
                'autoriza_pedido_utileria' => 1,
                'autorizado_por' => Auth::id(),
                'confirmado_at' => now(),
            ]);

            $subtotal = 0.0;
            $impuestos = 0.0;
            $total = 0.0;

            foreach ($sugerencia->detalles as $detalle) {
                $cantidad = $this->resolverCantidadDetalle($detalle);

                if ($cantidad <= 0) {
                    continue;
                }

                $precioUnitario = $this->resolverPrecioUnitario($detalle, $sugerencia);
                $tasaIva = $this->resolverTasaIva($detalle, $sugerencia);

                $importe = round($cantidad * $precioUnitario, 2);
                $impuestoIva = round($importe * $tasaIva, 2);
                $lineTotal = round($importe + $impuestoIva, 2);

                PedidoDetErp::query()->create([
                    'folio' => $pedido->id,
                    'articulo_id' => $detalle->producto_id,
                    'cantidad' => $cantidad,
                    'pu' => $precioUnitario,
                    'importe' => $importe,
                    'iva' => $tasaIva,
                    'impuesto_iva' => $impuestoIva,
                    'total' => $lineTotal,
                    'estatus' => 'GENERADO',
                    'observaciones' => $detalle->observaciones ?? '',
                    'motivo_id' => null,
                    'c_remisiona' => null,
                    'c_existencias' => null,
                ]);

                $subtotal += $importe;
                $impuestos += $impuestoIva;
                $total += $lineTotal;
            }

            if ($total <= 0) {
                throw new RuntimeException('La sugerencia no tiene productos con cantidad válida para generar pedido.');
            }

            $pedido->update([
                'subtotal' => round($subtotal, 2),
                'impuestos' => round($impuestos, 2),
                'total' => round($total, 2),
            ]);

            $sugerencia->update([
                'estatus' => 'procesado',
                'pedido_erp_id' => $pedido->id,
                'pedido_generado_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            return $pedido->fresh(['detalles', 'sugerencia']);
        });
    }

    protected function validarSugerencia(PedidoSugerencia $sugerencia): void
    {
        if ($sugerencia->estatus !== 'confirmado') {
            throw new RuntimeException('Solo se pueden generar pedidos desde sugerencias confirmadas.');
        }

        if (!empty($sugerencia->pedido_erp_id)) {
            throw new RuntimeException('La sugerencia ya tiene un pedido ERP generado.');
        }

        if ($sugerencia->detalles->isEmpty()) {
            throw new RuntimeException('La sugerencia no tiene detalles.');
        }
    }

    protected function resolverSerieYFolio(PedidoSugerencia $sugerencia): array
    {
        // TODO:
        // Reemplaza esta lógica con tu tabla real de series por sucursal/tipo.
        // Por ahora deja un fallback controlado.
        $serieId = 1;

        $ultimoFolio = PedidoErp::query()
            ->where('serie', $serieId)
            ->max('num_folio');

        $siguienteFolio = $ultimoFolio ? ((int) $ultimoFolio + 1) : 1;

        return [$serieId, $siguienteFolio];
    }

    protected function resolverCantidadDetalle($detalle): float
    {
        if (isset($detalle->cantidad_final) && $detalle->cantidad_final !== null) {
            return (float) $detalle->cantidad_final;
        }

        if (isset($detalle->cantidad_ajustada) && $detalle->cantidad_ajustada !== null) {
            return (float) $detalle->cantidad_ajustada;
        }

        if (isset($detalle->cantidad_sugerida) && $detalle->cantidad_sugerida !== null) {
            return (float) $detalle->cantidad_sugerida;
        }

        return 0;
    }

    protected function resolverPrecioUnitario($detalle, PedidoSugerencia $sugerencia): float
    {
        // TODO:
        // Sustituir por la lógica real de costos/precios de ERP si aplica.
        return 0;
    }

    protected function resolverTasaIva($detalle, PedidoSugerencia $sugerencia): float
    {
        // TODO:
        // Sustituir si cada artículo tiene IVA distinto.
        return 0;
    }

    protected function resolverUsuarioRealizo(): string
    {
        $user = Auth::user();

        if (!$user) {
            return 'SISTEMA';
        }

        return $user->name
            ?? $user->username
            ?? $user->email
            ?? ('USER_' . $user->id);
    }
}