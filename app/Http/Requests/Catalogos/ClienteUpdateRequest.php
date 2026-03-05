<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class ClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],

            'tipo_cliente_id' => ['sometimes', 'required', 'integer', 'exists:tipos_cliente,id'],
            'zona_id'         => ['nullable', 'integer', 'exists:zonas,id'],

            'back' => ['sometimes', 'array'],
            'back.contacto'       => ['nullable', 'string', 'max:255'],
            'back.telefono'       => ['nullable', 'string', 'max:30'],
            'back.email'          => ['nullable', 'email', 'max:255'],
            'back.direccion'      => ['nullable', 'string'],
            'back.cp'             => ['nullable', 'string', 'max:10'],
            'back.condicion_pago' => ['nullable', 'string', 'max:50'],
        ];
    }
}