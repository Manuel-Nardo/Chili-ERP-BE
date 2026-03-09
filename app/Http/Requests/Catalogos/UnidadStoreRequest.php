<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class UnidadStoreRequest extends FormRequest
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
        return [
            'clave' => ['required', 'string', 'max:20', 'unique:unidades,clave'],
            'nombre' => ['required', 'string', 'max:100', 'unique:unidades,nombre'],
            'abreviatura' => ['required', 'string', 'max:20'],
            'activo' => ['nullable', 'boolean'],
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
        ];
    }
}