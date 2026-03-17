<?php

namespace App\Http\Resources\PedidoSugerencia;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoSugerenciaResource extends JsonResource
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
            'cliente_id' => $this->cliente_id,
            'tipo_pedido_id' => $this->tipo_pedido_id,
            'fecha_objetivo' => optional($this->fecha_objetivo)->format('Y-m-d'),

            'estatus' => $this->estatus,
            'origen' => $this->origen,
            'modelo' => $this->modelo,
            'observaciones' => $this->observaciones,

            'es_editable' => $this->esEditable(),
            'esta_confirmado' => $this->estaConfirmado(),
            'esta_procesado' => $this->estaProcesado(),
            'esta_cancelado' => $this->estaCancelado(),

            'cliente' => $this->whenLoaded('cliente', function () {
                return [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'activo' => (bool) $this->cliente->activo,
                ];
            }),

            'tipo_pedido' => $this->whenLoaded('tipoPedido', function () {
                return [
                    'id' => $this->tipoPedido->id,
                    'nombre' => $this->tipoPedido->nombre,
                    'detalle' => $this->tipoPedido->detalle,
                    'activo' => (bool) $this->tipoPedido->activo,
                ];
            }),

            'creador' => $this->whenLoaded('creador', function () {
                return $this->creador ? [
                    'id' => $this->creador->id,
                    'name' => $this->creador->name,
                    'email' => $this->creador->email,
                ] : null;
            }),

            'editor' => $this->whenLoaded('editor', function () {
                return $this->editor ? [
                    'id' => $this->editor->id,
                    'name' => $this->editor->name,
                    'email' => $this->editor->email,
                ] : null;
            }),

            'detalles' => PedidoSugerenciaDetalleResource::collection(
                $this->whenLoaded('detalles')
            ),

            'totales' => [
                'cantidad_productos' => $this->whenLoaded('detalles', fn () => $this->detalles->count(), 0),
                'total_sugerido' => $this->whenLoaded('detalles', fn () => (float) $this->detalles->sum('cantidad_sugerida'), 0),
                'total_ajustado' => $this->whenLoaded('detalles', fn () => (float) $this->detalles->sum('cantidad_ajustada'), 0),
                'total_final' => $this->whenLoaded('detalles', fn () => (float) $this->detalles->sum('cantidad_final'), 0),
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}