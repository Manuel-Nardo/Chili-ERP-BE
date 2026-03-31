<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemisionErpResource extends JsonResource
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
            'pedido_erp_id' => $this->pedido_erp_id,
            'serie_id' => $this->serie_id,
            'folio' => $this->folio,
            'estatus' => $this->estatus,

            'fecha_remision' => $this->fecha_remision,
            'fecha_objetivo' => $this->fecha_objetivo,
            'confirmado_at' => $this->confirmado_at,

            'fecha_recepcion' => $this->fecha_recepcion,
            'recibido_por' => $this->recibido_por,
            'recibido_at' => $this->recibido_at,

            'sucursal_origen_id' => $this->sucursal_origen_id,
            'sucursal_destino_id' => $this->sucursal_destino_id,

            'subtotal' => (float) ($this->subtotal ?? 0),
            'impuestos' => (float) ($this->impuestos ?? 0),
            'total' => (float) ($this->total ?? 0),

            'creado_por' => $this->creado_por,
            'autorizado_por' => $this->autorizado_por,
            'autorizado_at' => $this->autorizado_at,

            'observaciones' => $this->observaciones,
            'observaciones_recepcion' => $this->observaciones_recepcion,

            'pedido' => $this->whenLoaded('pedido', function () {
                return [
                    'id' => $this->pedido?->id,
                    'folio' => $this->pedido?->folio,
                    'fecha_pedido' => $this->pedido?->fecha_pedido ?? null,
                    'fecha_objetivo' => $this->pedido?->fecha_objetivo ?? null,
                    'estatus' => $this->pedido?->estatus ?? null,
                ];
            }),

            'sucursal_origen' => $this->whenLoaded('sucursalOrigen', function () {
                return [
                    'id' => $this->sucursalOrigen?->id,
                    'nombre' => $this->sucursalOrigen?->nombre,
                ];
            }),

            'sucursal_destino' => $this->whenLoaded('sucursalDestino', function () {
                return [
                    'id' => $this->sucursalDestino?->id,
                    'nombre' => $this->sucursalDestino?->nombre,
                ];
            }),

            'detalles_count' => $this->whenCounted('detalles'),

            'detalles' => RemisionErpDetalleResource::collection(
                $this->whenLoaded('detalles')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}