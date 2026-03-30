<?php

namespace App\Services\Remisiones;

use App\Models\PedidoErp;
use App\Models\RemisionDetErp;
use App\Models\RemisionErp;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class RemisionErpService
{
    public function generarDesdePedido(PedidoErp $pedidoErp, array $payload, ?string $creadoPor = null): RemisionErp
    {
        return DB::transaction(function () use ($pedidoErp, $payload, $creadoPor) {
            $pedidoErp->load(['detalles.producto']);

            if ($pedidoErp->detalles->isEmpty()) {
                throw new RuntimeException('El pedido no tiene detalles para remisionar.');
            }

            $remisionadoPorDetalle = DB::table('remisiones_det_erp as rde')
                ->join('remisiones_erp as re', 're.id', '=', 'rde.remision_id')
                ->where('re.pedido_erp_id', $pedidoErp->id)
                ->select(
                    'rde.pedido_det_erp_id',
                    DB::raw('SUM(rde.cantidad) as total_remisionado')
                )
                ->groupBy('rde.pedido_det_erp_id')
                ->pluck('total_remisionado', 'rde.pedido_det_erp_id');

            $detallesPedido = $pedidoErp->detalles->keyBy('id');
            $detallesInput = collect($payload['detalles'] ?? [])
                ->map(function ($row) {
                    return [
                        'pedido_det_erp_id' => (int) $row['pedido_det_erp_id'],
                        'cantidad' => (float) $row['cantidad'],
                    ];
                })
                ->filter(fn ($row) => $row['cantidad'] > 0)
                ->values();

            if ($detallesInput->isEmpty()) {
                throw new InvalidArgumentException('Debes indicar al menos una partida con cantidad mayor a cero.');
            }

            $subtotal = 0.0;
            $impuestos = 0.0;
            $total = 0.0;

            $lineas = [];

            foreach ($detallesInput as $input) {
                /** @var \App\Models\PedidoDetErp|null $detallePedido */
                $detallePedido = $detallesPedido->get($input['pedido_det_erp_id']);

                if (!$detallePedido) {
                    throw new InvalidArgumentException("La partida {$input['pedido_det_erp_id']} no pertenece al pedido.");
                }

                $cantidadPedida = (float) $detallePedido->cantidad;
                $cantidadRemisionada = (float) ($remisionadoPorDetalle[$detallePedido->id] ?? 0);
                $cantidadPendiente = max(0, $cantidadPedida - $cantidadRemisionada);
                $cantidadSolicitada = (float) $input['cantidad'];

                if ($cantidadSolicitada > $cantidadPendiente) {
                    throw new InvalidArgumentException(
                        "La cantidad de la partida {$detallePedido->id} excede lo pendiente. Pendiente: {$cantidadPendiente}."
                    );
                }

                $precioUnitario = (float) $detallePedido->precio_unitario;
                $importe = round($cantidadSolicitada * $precioUnitario, 2);

                $baseCantidadPedido = (float) max($cantidadPedida, 0.000001);
                $factor = $cantidadSolicitada / $baseCantidadPedido;

                $impuestoIva = round((float) ($detallePedido->impuesto_iva ?? 0) * $factor, 2);
                $iva = (float) ($detallePedido->iva ?? 0);
                $totalLinea = round($importe + $impuestoIva, 2);

                $subtotal += $importe;
                $impuestos += $impuestoIva;
                $total += $totalLinea;

                $lineas[] = [
                    'pedido_det_erp_id' => $detallePedido->id,
                    'articulo_id' => $detallePedido->articulo_id,
                    'cantidad' => $cantidadSolicitada,
                    'precio_unitario' => $precioUnitario,
                    'importe' => $importe,
                    'iva' => $iva,
                    'impuesto_iva' => $impuestoIva,
                    'total' => $totalLinea,
                    'estatus' => 'GENERADA',
                    'observaciones' => $detallePedido->observaciones,
                ];
            }

            if (empty($lineas)) {
                throw new InvalidArgumentException('No hay partidas válidas para generar la remisión.');
            }

            $folio = ((int) RemisionErp::where('serie_id', $pedidoErp->serie_id)->max('folio')) + 1;

            $remision = RemisionErp::create([
                'pedido_erp_id' => $pedidoErp->id,
                'serie_id' => $pedidoErp->serie_id,
                'folio' => $folio,
                'estatus' => 'GENERADA',
                'fecha_remision' => $payload['fecha_remision'],
                'fecha_objetivo' => $payload['fecha_objetivo'] ?? $pedidoErp->fecha_objetivo,
                'sucursal_origen_id' => $pedidoErp->sucursal_origen_id,
                'sucursal_destino_id' => $pedidoErp->sucursal_destino_id,
                'subtotal' => round($subtotal, 2),
                'impuestos' => round($impuestos, 2),
                'total' => round($total, 2),
                'creado_por' => $creadoPor,
                'observaciones' => $payload['observaciones'] ?? null,
            ]);

            foreach ($lineas as $linea) {
                $linea['remision_id'] = $remision->id;
                RemisionDetErp::create($linea);
            }

            $this->actualizarEstatusPedido($pedidoErp);

            return $remision->load([
                'detalles.producto',
                'pedido',
                'serieSucursal',
                'sucursalOrigen',
                'sucursalDestino',
            ]);
        });
    }

    protected function actualizarEstatusPedido(PedidoErp $pedidoErp): void
    {
        $pedidoErp->load('detalles');

        $remisionadoPorDetalle = DB::table('remisiones_det_erp as rde')
            ->join('remisiones_erp as re', 're.id', '=', 'rde.remision_id')
            ->where('re.pedido_erp_id', $pedidoErp->id)
            ->select(
                'rde.pedido_det_erp_id',
                DB::raw('SUM(rde.cantidad) as total_remisionado')
            )
            ->groupBy('rde.pedido_det_erp_id')
            ->pluck('total_remisionado', 'rde.pedido_det_erp_id');

        $hayRemisionado = false;
        $todoCompleto = true;

        foreach ($pedidoErp->detalles as $detalle) {
            $cantidadPedida = (float) $detalle->cantidad;
            $cantidadRemisionada = (float) ($remisionadoPorDetalle[$detalle->id] ?? 0);

            if ($cantidadRemisionada > 0) {
                $hayRemisionado = true;
            }

            if ($cantidadRemisionada < $cantidadPedida) {
                $todoCompleto = false;
            }
        }

        $nuevoEstatus = 'GENERADO';

        if ($todoCompleto && $hayRemisionado) {
            $nuevoEstatus = 'REMISIONADO';
        } elseif ($hayRemisionado) {
            $nuevoEstatus = 'PARCIALMENTE_REMISIONADO';
        }

        $pedidoErp->update([
            'estatus' => $nuevoEstatus,
        ]);
    }
}