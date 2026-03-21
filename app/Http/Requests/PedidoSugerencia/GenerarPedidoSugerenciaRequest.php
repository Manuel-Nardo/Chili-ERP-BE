<?php

namespace App\Http\Requests\PedidoSugerencia;

use Illuminate\Foundation\Http\FormRequest;

class GenerarPedidoSugerenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'tipo_pedido_id' => ['required', 'integer', 'exists:tipos_pedido,id'],
            'fecha_objetivo' => ['required', 'date'],
            'dias_historico' => ['nullable', 'integer', 'min:28', 'max:365'],
            'forzar_regeneracion' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}