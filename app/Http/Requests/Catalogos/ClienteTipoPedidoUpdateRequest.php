<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteTipoPedidoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $asignacion = $this->route('cliente_tipo_pedido');
        $clienteId = $this->input('cliente_id', $asignacion?->cliente_id);
        $tipoPedidoId = $this->input('tipo_pedido_id', $asignacion?->tipo_pedido_id);

        return [
            'cliente_id' => ['sometimes', 'required', 'integer', 'exists:clientes,id'],
            'tipo_pedido_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:tipos_pedido,id',
                Rule::unique('clientes_tipos_pedido', 'tipo_pedido_id')
                    ->where(fn ($q) => $q->where('cliente_id', $clienteId))
                    ->ignore($asignacion?->id),
            ],
            'usar_horario_default' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}