<?php

namespace App\Services;

use App\Models\PedidoSugerencia;
use App\Models\PedidoSugerenciaDetalle;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PedidoSugerenciaService
{
    public function create(array $data, ?int $userId = null): PedidoSugerencia
    {
        return DB::transaction(function () use ($data, $userId) {
            $detalles = $data['detalles'] ?? [];

            $this->validarProductosContraTipoPedido(
                (int) $data['tipo_pedido_id'],
                $detalles
            );

            $sugerencia = PedidoSugerencia::create([
                'cliente_id' => $data['cliente_id'],
                'tipo_pedido_id' => $data['tipo_pedido_id'],
                'fecha_objetivo' => $data['fecha_objetivo'],
                'estatus' => PedidoSugerencia::ESTATUS_BORRADOR,
                'origen' => $data['origen'],
                'modelo' => $data['modelo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->syncDetalles($sugerencia, $detalles);

            return $sugerencia->load([
                'cliente',
                'tipoPedido',
                'detalles.producto',
                'creador',
                'editor',
            ]);
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
                'cliente_id' => $data['cliente_id'],
                'tipo_pedido_id' => $data['tipo_pedido_id'],
                'fecha_objetivo' => $data['fecha_objetivo'],
                'origen' => $data['origen'],
                'modelo' => $data['modelo'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'updated_by' => $userId,
            ]);

            $this->syncDetalles($sugerencia, $detalles);

            return $sugerencia->load([
                'cliente',
                'tipoPedido',
                'detalles.producto',
                'creador',
                'editor',
            ]);
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

        return $sugerencia->load([
            'cliente',
            'tipoPedido',
            'detalles.producto',
            'creador',
            'editor',
        ]);
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

        return $sugerencia->load([
            'cliente',
            'tipoPedido',
            'detalles.producto',
            'creador',
            'editor',
        ]);
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
                'producto_id' => $detalle['producto_id'],
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

        $productosExistentes = Producto::query()
            ->whereIn('id', $productoIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $faltantes = array_values(array_diff($productoIds->all(), $productosExistentes));

        if (! empty($faltantes)) {
            throw new InvalidArgumentException(
                'Uno o más productos enviados no existen en el catálogo.'
            );
        }
    }
}