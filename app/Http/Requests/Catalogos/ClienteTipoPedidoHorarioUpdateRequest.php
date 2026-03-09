<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteTipoPedidoHorarioUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $horario = $this->route('cliente_tipo_pedido_horario');
        $clienteTipoPedidoId = $this->input('cliente_tipo_pedido_id', $horario?->cliente_tipo_pedido_id);

        return [
            'cliente_tipo_pedido_id' => ['sometimes', 'required', 'integer', 'exists:clientes_tipos_pedido,id'],
            'dia_semana' => [
                'sometimes',
                'required',
                'integer',
                'between:1,7',
                Rule::unique('clientes_tipos_pedido_horarios', 'dia_semana')
                    ->where(fn ($q) => $q->where('cliente_tipo_pedido_id', $clienteTipoPedidoId))
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
            $horario = $this->route('cliente_tipo_pedido_horario');

            $horaInicio = $this->input('hora_inicio', $horario?->hora_inicio);
            $horaFin = $this->input('hora_fin', $horario?->hora_fin);

            if ($horaInicio && $horaFin && $horaInicio >= $horaFin) {
                $validator->errors()->add('hora_inicio', 'La hora de inicio debe ser menor que la hora fin.');
            }
        });
    }
}