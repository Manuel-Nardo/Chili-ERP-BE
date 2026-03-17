<?php

namespace App\Http\Requests\PedidoSugerencia;

use App\Models\PedidoSugerencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePedidoSugerenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('origen') && is_string($this->origen)) {
            $this->merge([
                'origen' => trim(mb_strtolower($this->origen)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'tipo_pedido_id' => ['required', 'integer', 'exists:tipos_pedido,id'],
            'fecha_objetivo' => ['required', 'date'],
            'origen' => ['required', Rule::in(PedidoSugerencia::origenesDisponibles())],
            'modelo' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad_sugerida' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.cantidad_ajustada' => ['required', 'numeric', 'min:0'],
            'detalles.*.cantidad_final' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.observaciones' => ['nullable', 'string'],
            'detalles.*.metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'tipo_pedido_id' => 'tipo de pedido',
            'fecha_objetivo' => 'fecha objetivo',
            'origen' => 'origen',
            'modelo' => 'modelo',
            'observaciones' => 'observaciones',
            'detalles' => 'detalles',
            'detalles.*.producto_id' => 'producto',
            'detalles.*.cantidad_sugerida' => 'cantidad sugerida',
            'detalles.*.cantidad_ajustada' => 'cantidad ajustada',
            'detalles.*.cantidad_final' => 'cantidad final',
            'detalles.*.observaciones' => 'observaciones del detalle',
            'detalles.*.metadata' => 'metadata',
        ];
    }

    public function messages(): array
    {
        return [
            'detalles.required' => 'Debes enviar al menos un producto en la sugerencia.',
            'detalles.array' => 'El campo detalles debe ser un arreglo.',
            'detalles.min' => 'Debes enviar al menos un producto en la sugerencia.',
        ];
    }
}