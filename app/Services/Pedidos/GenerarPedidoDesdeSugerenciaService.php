<?php

namespace App\Services\Pedidos;

use App\Models\PedidoDetErp;
use App\Models\PedidoErp;
use App\Models\PedidoSugerencia;
use App\Models\Producto;
use App\Models\SerieSucursal;
use App\Models\TipoSerie;
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
                ->with([
                    'detalles.producto.impuestos',
                    'detalles.producto.precios',
                ])
                ->lockForUpdate()
                ->findOrFail($pedidoSugerenciaId);

            $this->validarSugerencia($sugerencia);

            [$serieId, $folio] = $this->resolverSerieYFolio($sugerencia);

            $pedido = PedidoErp::query()->create([
                'serie_id' => $serieId,
                'folio' => $folio,
                'tipo_pedido_id' => $sugerencia->tipo_pedido_id,
                'estatus' => 'GENERADO',
                'fecha_pedido' => now()->toDateString(),
                'fecha_objetivo' => $sugerencia->fecha_objetivo,
                'confirmado_at' => now(),
                'sucursal_origen_id' => null,
                'sucursal_destino_id' => $sugerencia->cliente_id,
                'origen_tipo' => 'forecast',
                'origen_id' => $sugerencia->id,
                'pedido_sugerencia_id' => $sugerencia->id,
                'subtotal' => 0,
                'impuestos' => 0,
                'total' => 0,
                'creado_por' => $this->resolverUsuarioRealizo(),
                'autorizado_por' => Auth::id(),
                'autorizado_at' => now(),
                'observaciones' => $sugerencia->observaciones ?? '',
            ]);

            $subtotal = 0.0;
            $impuestos = 0.0;
            $total = 0.0;
            $productosProcesados = 0;
            $productosConCantidadValida = 0;

            foreach ($sugerencia->detalles as $detalle) {
                $cantidad = $this->resolverCantidadDetalle($detalle);

                if ($cantidad <= 0) {
                    continue;
                }

                $productosConCantidadValida++;

                $precioUnitario = $this->resolverPrecioUnitario($detalle);
                $tasaIva = $this->resolverTasaIva($detalle);

                if ($precioUnitario <= 0) {
                    continue;
                }

                $importe = round($cantidad * $precioUnitario, 2);
                $impuestoIva = round($importe * $tasaIva, 2);
                $lineTotal = round($importe + $impuestoIva, 2);

                PedidoDetErp::query()->create([
                    'pedido_id' => $pedido->id,
                    'articulo_id' => $detalle->producto_id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'importe' => $importe,
                    'iva' => $tasaIva,
                    'impuesto_iva' => $impuestoIva,
                    'total' => $lineTotal,
                    'estatus' => 'GENERADO',
                    'observaciones' => $detalle->observaciones ?? '',
                ]);

                $subtotal += $importe;
                $impuestos += $impuestoIva;
                $total += $lineTotal;
                $productosProcesados++;
            }

            if ($productosConCantidadValida === 0) {
                throw new RuntimeException('La sugerencia no tiene productos con cantidad válida para generar pedido.');
            }

            if ($productosProcesados === 0 || $total <= 0) {
                throw new RuntimeException('No se pudo generar el pedido porque los productos no tienen precio unitario válido.');
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

            return $pedido->fresh([
                'detalles',
                'sugerencia',
                'serieSucursal',
                'tipoPedido',
            ]);
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
        $tipoSerieId = $this->resolverTipoSeriePedidoId();

        $serie = SerieSucursal::query()
            ->where('cliente_id', $sugerencia->cliente_id)
            ->where('tipo_serie_id', $tipoSerieId)
            ->where('activo', true)
            ->lockForUpdate()
            ->first();

        if (!$serie) {
            throw new RuntimeException('No existe una serie activa configurada para este cliente y tipo de serie de pedido.');
        }

        $siguienteFolio = ((int) $serie->folio_actual) + 1;

        $serie->update([
            'folio_actual' => $siguienteFolio,
        ]);

        return [(int) $serie->id, $siguienteFolio];
    }

    protected function resolverTipoSeriePedidoId(): int
    {
        $tipoSerie = TipoSerie::query()
            ->where('activo', true)
            ->where(function ($query) {
                $query->where('clave', 'PED')
                    ->orWhere('clave', 'PEDIDO')
                    ->orWhere('nombre', 'PEDIDOS')
                    ->orWhere('nombre', 'PEDIDO');
            })
            ->first();

        if (!$tipoSerie) {
            throw new RuntimeException('No existe un tipo de serie configurado para pedidos.');
        }

        return (int) $tipoSerie->id;
    }

    protected function resolverCantidadDetalle($detalle): float
    {
        $final = isset($detalle->cantidad_final) ? (float) $detalle->cantidad_final : null;
        $ajustada = isset($detalle->cantidad_ajustada) ? (float) $detalle->cantidad_ajustada : null;
        $sugerida = isset($detalle->cantidad_sugerida) ? (float) $detalle->cantidad_sugerida : null;

        if ($final !== null && $final > 0) {
            return $final;
        }

        if ($ajustada !== null && $ajustada > 0) {
            return $ajustada;
        }

        if ($sugerida !== null && $sugerida > 0) {
            return $sugerida;
        }

        return 0;
    }

    protected function resolverPrecioUnitario($detalle): float
    {
        $producto = $detalle->producto;

        if (!$producto instanceof Producto) {
            return 0;
        }

        if ($producto->precio_actual !== null && (float) $producto->precio_actual > 0) {
            return round((float) $producto->precio_actual, 2);
        }

        $hoy = now()->toDateString();

        $precioVigente = $producto->precios
            ->filter(function ($precio) use ($hoy) {
                $fechaInicio = $precio->fecha_inicio?->format('Y-m-d');
                $fechaFin = $precio->fecha_fin?->format('Y-m-d');

                $inicioValido = !$fechaInicio || $fechaInicio <= $hoy;
                $finValido = !$fechaFin || $fechaFin >= $hoy;

                return $inicioValido && $finValido;
            })
            ->sortByDesc(function ($precio) {
                return $precio->fecha_inicio?->timestamp ?? 0;
            })
            ->first();

        if ($precioVigente && (float) $precioVigente->precio > 0) {
            return round((float) $precioVigente->precio, 2);
        }

        return 0;
    }

    protected function resolverTasaIva($detalle): float
    {
        $producto = $detalle->producto;

        if (!$producto instanceof Producto) {
            return 0;
        }

        $iva = $producto->impuestos
            ->first(function ($impuesto) {
                return $impuesto->activo
                    && strtoupper((string) $impuesto->tipo) === 'IVA';
            });

        if (!$iva) {
            return 0;
        }

        return round(((float) $iva->porcentaje) / 100, 6);
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