<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // luego lo amarras a super_admin con middleware/policy
    }

    public function rules(): array
    {
        $guard = $this->input('guard_name', config('auth.defaults.guard', 'web'));

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // convención: modulo.accion (users.create, rbac.roles.update, etc.)
                'regex:/^[a-z0-9]+(\.[a-z0-9_-]+)+$/',
                Rule::unique('permissions', 'name')->where(fn ($q) => $q->where('guard_name', $guard)),
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