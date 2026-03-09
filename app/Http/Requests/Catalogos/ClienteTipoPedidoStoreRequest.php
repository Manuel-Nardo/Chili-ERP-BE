<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class ClienteTipoPedidoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'tipo_pedido_id' => ['required', 'integer', 'exists:tipos_pedido,id'],
            'usar_horario_default' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}