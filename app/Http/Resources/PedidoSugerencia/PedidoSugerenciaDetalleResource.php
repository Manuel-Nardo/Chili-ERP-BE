<?php

namespace App\Http\Resources\PedidoSugerencia;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoSugerenciaDetalleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pedido_sugerencia_id' => $this->pedido_sugerencia_id,
            'producto_id' => $this->producto_id,

            'cantidad_sugerida' => (float) $this->cantidad_sugerida,
            'cantidad_ajustada' => (float) $this->cantidad_ajustada,
            'cantidad_final' => (float) $this->cantidad_final,

            'observaciones' => $this->observaciones,
            'metadata' => $this->metadata,

            'producto' => $this->whenLoaded('producto', function () {
                return [
                    'id' => $this->producto->id,
                    'clave' => $this->producto->clave,
                    'nombre' => $this->producto->nombre,
                    'descripcion' => $this->producto->descripcion,
                    'tipo_pedido_id' => $this->producto->tipo_pedido_id,
                    'ruta' => $this->producto->ruta,
                    'activo' => (bool) $this->producto->activo,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}