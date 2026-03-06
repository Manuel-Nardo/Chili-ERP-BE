<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class TipoPedidoHorarioStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_pedido_id' => ['required', 'integer', 'exists:tipos_pedido,id'],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $horaInicio = $this->input('hora_inicio');
            $horaFin = $this->input('hora_fin');

            if ($horaInicio && $horaFin && $horaInicio >= $horaFin) {
                $validator->errors()->add('hora_inicio', 'La hora de inicio debe ser menor que la hora fin.');
            }
        });
    }
}