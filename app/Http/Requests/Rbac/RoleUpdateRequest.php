<?php

namespace App\Http\Requests\Rbac;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \Spatie\Permission\Models\Role $role */
        $role = $this->route('role');
        $guard = $role?->guard_name ?? config('auth.defaults.guard', 'web');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique((new Role)->getTable(), 'name')
                    ->where(fn($q) => $q->where('guard_name', $guard))
                    ->ignore($role?->id),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required'],
        ];
    }
}