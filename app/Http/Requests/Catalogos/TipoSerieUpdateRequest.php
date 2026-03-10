<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoSerieUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipoSerie = $this->route('tipo_serie');
        $id = is_object($tipoSerie) ? $tipoSerie->id : $tipoSerie;

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('tipos_serie', 'nombre')->ignore($id)],
            'clave' => ['required', 'string', 'max:20', Rule::unique('tipos_serie', 'clave')->ignore($id)],
            'activo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido.',
            'nombre.unique' => 'El nombre ya existe.',
            'clave.required' => 'La clave es requerida.',
            'clave.unique' => 'La clave ya existe.',
            'activo.required' => 'El campo activo es requerido.',
        ];
    }
}