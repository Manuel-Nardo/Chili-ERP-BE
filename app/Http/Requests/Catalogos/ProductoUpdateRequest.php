<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $producto = $this->route('producto');
        $productoId = is_object($producto) ? $producto->id : $producto;

        return [
            'clave' => ['required', 'integer', Rule::unique('productos', 'clave')->ignore($productoId)],
            'clave_sat' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['required', 'boolean'],
            'facturable' => ['required', 'boolean'],

            'linea_id' => ['required', 'integer', 'exists:lineas,id'],

            'tipos_pedido_ids' => ['required', 'array', 'min:1'],
            'tipos_pedido_ids.*' => ['integer', 'exists:tipos_pedido,id'],

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
            'activo.required' => 'El campo activo es requerido.',
            'facturable.required' => 'El campo facturable es requerido.',
            'linea_id.required' => 'La línea es requerida.',
            'linea_id.exists' => 'La línea seleccionada no existe.',

            'tipos_pedido_ids.required' => 'Debes seleccionar al menos un tipo de pedido.',
            'tipos_pedido_ids.array' => 'El campo tipos de pedido debe ser un arreglo.',
            'tipos_pedido_ids.min' => 'Debes seleccionar al menos un tipo de pedido.',
            'tipos_pedido_ids.*.exists' => 'Uno o más tipos de pedido no existen.',

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