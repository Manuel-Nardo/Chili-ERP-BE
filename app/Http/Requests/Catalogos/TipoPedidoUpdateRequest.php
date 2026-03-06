<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoPedidoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('tipo_pedido')?->id ?? $this->route('tipo_pedido');

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('tipos_pedido', 'nombre')->ignore($id)],
            'detalle' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}