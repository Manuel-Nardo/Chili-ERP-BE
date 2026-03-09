<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => ['required', 'integer', 'unique:productos,clave'],
            'clave_sat' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'facturable' => ['nullable', 'boolean'],

            'linea_id' => ['required', 'integer', 'exists:lineas,id'],
            'tipo_pedido_id' => ['required', 'integer', 'exists:tipos_pedido,id'],
            'medida_id' => ['required', 'integer', 'exists:unidades,id'],
            'medida_compra_id' => ['required', 'integer', 'exists:unidades,id'],

            'ruta' => ['nullable', Rule::in(['FRIA', 'CALIENTE', 'PAN'])],

            'precio_actual' => ['nullable', 'numeric', 'min:0'],
            'costo_actual' => ['nullable', 'numeric', 'min:0'],

            'impuestos' => ['nullable', 'array'],
            'impuestos.*' => ['integer', 'exists:impuestos,id'],

            'motivo_precio' => ['nullable', 'string', 'max:255'],
            'motivo_costo' => ['nullable', 'string', 'max:255'],
            'fecha_inicio_precio' => ['nullable', 'date'],
            'fecha_inicio_costo' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave es requerida.',
            'clave.unique' => 'La clave ya existe.',
            'nombre.required' => 'El nombre es requerido.',
            'linea_id.required' => 'La línea es requerida.',
            'linea_id.exists' => 'La línea seleccionada no existe.',
            'tipo_pedido_id.required' => 'El tipo de pedido es requerido.',
            'tipo_pedido_id.exists' => 'El tipo de pedido seleccionado no existe.',
            'medida_id.required' => 'La unidad es requerida.',
            'medida_id.exists' => 'La unidad seleccionada no existe.',
            'medida_compra_id.required' => 'La unidad de compra es requerida.',
            'medida_compra_id.exists' => 'La unidad de compra seleccionada no existe.',
            'ruta.in' => 'La ruta seleccionada no es válida.',
            'impuestos.array' => 'El campo impuestos debe ser un arreglo.',
            'impuestos.*.exists' => 'Uno o más impuestos no existen.',
        ];
    }
}