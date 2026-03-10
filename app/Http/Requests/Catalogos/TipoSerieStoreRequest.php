<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class TipoSerieStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:tipos_serie,nombre'],
            'clave' => ['required', 'string', 'max:20', 'unique:tipos_serie,clave'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido.',
            'nombre.unique' => 'El nombre ya existe.',
            'clave.required' => 'La clave es requerida.',
            'clave.unique' => 'La clave ya existe.',
        ];
    }
}