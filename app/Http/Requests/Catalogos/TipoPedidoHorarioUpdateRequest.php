<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoPedidoHorarioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $horario = $this->route('tipo_pedido_horario');
        $tipoPedidoId = $this->input('tipo_pedido_id', $horario?->tipo_pedido_id);
        $diaSemana = $this->input('dia_semana', $horario?->dia_semana);

        return [
            'tipo_pedido_id' => ['sometimes', 'required', 'integer', 'exists:tipos_pedido,id'],
            'dia_semana' => [
                'sometimes',
                'required',
                'integer',
                'between:1,7',
                Rule::unique('tipos_pedido_horarios', 'dia_semana')
                    ->where(fn ($q) => $q->where('tipo_pedido_id', $tipoPedidoId))
                    ->ignore($horario?->id),
            ],
            'hora_inicio' => ['sometimes', 'required', 'date_format:H:i'],
            'hora_fin' => ['sometimes', 'required', 'date_format:H:i'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $horario = $this->route('tipo_pedido_horario');

            $horaInicio = $this->input('hora_inicio', $horario?->hora_inicio);
            $horaFin = $this->input('hora_fin', $horario?->hora_fin);

            if ($horaInicio && $horaFin && $horaInicio >= $horaFin) {
                $validator->errors()->add('hora_inicio', 'La hora de inicio debe ser menor que la hora fin.');
            }
        });
    }
}