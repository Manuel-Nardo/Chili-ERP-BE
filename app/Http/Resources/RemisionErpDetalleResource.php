<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemisionErpDetalleResource extends JsonResource
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
            'remision_id' => $this->remision_id,
            'pedido_det_erp_id' => $this->pedido_det_erp_id,
            'articulo_id' => $this->articulo_id,

            'cantidad' => (float) $this->cantidad,
            'cantidad_recibida' => (float) ($this->cantidad_recibida ?? 0),
            'diferencia' => (float) ($this->diferencia ?? 0),

            'precio_unitario' => (float) ($this->precio_unitario ?? 0),
            'importe' => (float) ($this->importe ?? 0),
            'iva' => $this->iva !== null ? (float) $this->iva : null,
            'impuesto_iva' => $this->impuesto_iva !== null ? (float) $this->impuesto_iva : null,
            'total' => (float) ($this->total ?? 0),

            'estatus' => $this->estatus,
            'observaciones' => $this->observaciones,
            'observaciones_recepcion' => $this->observaciones_recepcion,

            'producto' => $this->whenLoaded('producto', function () {
                return [
                    'id' => $this->producto?->id,
                    'nombre' => $this->producto?->nombre,
                    'clave' => $this->producto?->clave ?? null,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}