<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o gate/policy
    }

    public function rules(): array
    {
        $guard = $this->input('guard_name', config('auth.defaults.guard', 'web'));

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique((new Role)->getTable(), 'name')->where(fn($q) => $q->where('guard_name', $guard)),
            ],
            'guard_name' => ['nullable', 'string', 'max:50'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required'], // ids o strings (validados por resolvePermissionIds)
        ];
    }
}