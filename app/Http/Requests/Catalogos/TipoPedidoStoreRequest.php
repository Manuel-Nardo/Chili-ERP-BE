<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class TipoPedidoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:tipos_pedido,nombre'],
            'detalle' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}