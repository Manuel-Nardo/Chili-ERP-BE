<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecibirRemisionErpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $detalles = collect($this->input('detalles', []))
            ->map(function ($detalle) {
                return [
                    'id' => isset($detalle['id']) ? (int) $detalle['id'] : null,
                    'cantidad_recibida' => isset($detalle['cantidad_recibida'])
                        ? (float) $detalle['cantidad_recibida']
                        : null,
                    'observaciones_recepcion' => $detalle['observaciones_recepcion'] ?? null,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'id' => isset($this->id) ? (int) $this->input('id') : null,
            'fecha_recepcion' => $this->input('fecha_recepcion'),
            'observaciones_recepcion' => $this->input('observaciones_recepcion'),
            'detalles' => $detalles,
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:remisiones_erp,id'],
            'fecha_recepcion' => ['required', 'date'],
            'observaciones_recepcion' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.id' => ['required', 'integer', 'exists:remisiones_det_erp,id'],
            'detalles.*.cantidad_recibida' => ['required', 'numeric', 'min:0'],
            'detalles.*.observaciones_recepcion' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'id de la remisión',
            'fecha_recepcion' => 'fecha de recepción',
            'observaciones_recepcion' => 'observaciones de recepción',
            'detalles' => 'detalles',
            'detalles.*.id' => 'id del detalle',
            'detalles.*.cantidad_recibida' => 'cantidad recibida',
            'detalles.*.observaciones_recepcion' => 'observaciones del detalle',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Debes enviar el id de la remisión.',
            'id.integer' => 'El id de la remisión debe ser numérico.',
            'id.exists' => 'La remisión indicada no existe.',

            'fecha_recepcion.required' => 'Debes enviar la fecha de recepción.',
            'fecha_recepcion.date' => 'La fecha de recepción no tiene un formato válido.',

            'detalles.required' => 'Debes enviar al menos un detalle para recibir la remisión.',
            'detalles.array' => 'El campo detalles debe ser un arreglo.',
            'detalles.min' => 'Debes enviar al menos un detalle para recibir la remisión.',

            'detalles.*.id.required' => 'Cada detalle debe incluir su id.',
            'detalles.*.id.integer' => 'El id del detalle debe ser numérico.',
            'detalles.*.id.exists' => 'Uno de los detalles enviados no existe.',

            'detalles.*.cantidad_recibida.required' => 'Cada detalle debe incluir la cantidad recibida.',
            'detalles.*.cantidad_recibida.numeric' => 'La cantidad recibida debe ser numérica.',
            'detalles.*.cantidad_recibida.min' => 'La cantidad recibida no puede ser negativa.',
        ];
    }
}