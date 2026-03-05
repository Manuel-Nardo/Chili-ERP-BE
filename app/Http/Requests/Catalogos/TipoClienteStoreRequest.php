<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

class TipoClienteStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'clave'  => ['required', 'string', 'max:50', 'unique:tipos_cliente,clave'],
            'nombre' => ['required', 'string', 'max:120'],
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