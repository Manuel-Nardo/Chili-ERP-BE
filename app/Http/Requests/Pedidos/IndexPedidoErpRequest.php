<?php

namespace App\Http\Requests\Pedidos;

use Illuminate\Foundation\Http\FormRequest;

class IndexPedidoErpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estatus' => ['nullable', 'string'],
            'tipo_pedido_id' => ['nullable', 'integer', 'exists:tipos_pedido,id'],
            'sucursal_destino_id' => ['nullable', 'integer', 'exists:sucursales,id'],

            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],

            'search' => ['nullable', 'string', 'max:255'],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}