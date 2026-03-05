<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TipoClienteUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('tipos_cliente')?->id ?? $this->route('tipos_cliente') ?? $this->route('tipos-cliente');

        return [
            'clave'  => ['sometimes', 'required', 'string', 'max:50', Rule::unique('tipos_cliente', 'clave')->ignore($id)],
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('clave')) {
            $this->merge(['clave' => strtoupper(trim((string) $this->input('clave')))]);
        }
    }
}