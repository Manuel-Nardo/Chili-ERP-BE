<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImpuestoUpdateRequest extends FormRequest
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
        $impuesto = $this->route('impuesto');
        $impuestoId = is_object($impuesto) ? $impuesto->id : $impuesto;

        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('impuestos', 'nombre')->ignore($impuestoId),
            ],
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('impuestos', 'codigo')->ignore($impuestoId),
            ],
            'tipo' => ['required', 'string', Rule::in(['IVA', 'IEPS', 'ISR', 'OTRO'])],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'activo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es requerido.',
            'nombre.unique' => 'El nombre ya existe.',
            'codigo.required' => 'El código es requerido.',
            'codigo.unique' => 'El código ya existe.',
            'tipo.required' => 'El tipo es requerido.',
            'tipo.in' => 'El tipo seleccionado no es válido.',
            'porcentaje.required' => 'El porcentaje es requerido.',
            'porcentaje.numeric' => 'El porcentaje debe ser numérico.',
            'activo.required' => 'El campo activo es requerido.',
        ];
    }
}