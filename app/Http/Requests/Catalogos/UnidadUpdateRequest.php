<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnidadUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unidad = $this->route('unidad');

        $unidadId = is_object($unidad) ? $unidad->id : $unidad;

        return [
            'clave' => [
                'required',
                'string',
                'max:20',
                Rule::unique('unidades', 'clave')->ignore($unidadId),
            ],
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('unidades', 'nombre')->ignore($unidadId),
            ],
            'abreviatura' => ['required', 'string', 'max:20'],
            'activo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave es requerida.',
            'clave.unique' => 'La clave ya existe.',
            'nombre.required' => 'El nombre es requerido.',
            'nombre.unique' => 'El nombre ya existe.',
            'abreviatura.required' => 'La abreviatura es requerida.',
            'activo.required' => 'El campo activo es requerido.',
        ];
    }
}