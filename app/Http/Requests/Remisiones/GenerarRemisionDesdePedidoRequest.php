<?php

namespace App\Http\Requests\Remisiones;

use Illuminate\Foundation\Http\FormRequest;

class GenerarRemisionDesdePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_remision' => ['required', 'date'],
            'fecha_objetivo' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.pedido_det_erp_id' => ['required', 'integer', 'exists:pedidos_det_erp,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ];
    }
}