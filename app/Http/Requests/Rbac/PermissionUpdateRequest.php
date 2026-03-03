<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guard = $this->input('guard_name', config('auth.defaults.guard', 'web'));
        $id = $this->route('permission')?->id; // model binding

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(\.[a-z0-9_-]+)+$/',
                Rule::unique('permissions', 'name')
                    ->where(fn ($q) => $q->where('guard_name', $guard))
                    ->ignore($id),
            ],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Formato inválido. Usa "modulo.accion" (ej: users.create).',
        ];
    }
}