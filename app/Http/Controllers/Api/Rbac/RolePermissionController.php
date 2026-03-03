<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    private string $guard = 'web';

    public function sync(Request $request, Role $role)
    {
        abort_if($role->guard_name !== $this->guard, 404);

        $data = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string'], // names (recomendado)
        ]);

        $names = array_values(array_unique($data['permissions']));

        // Solo permisos del mismo guard + existentes
        $validNames = Permission::query()
            ->where('guard_name', $this->guard)
            ->whereIn('name', $names)
            ->pluck('name')
            ->values()
            ->all();

        $role->syncPermissions($validNames);

        return response()->json([
            'success' => true,
            'message' => 'Permisos sincronizados.',
            'data' => [
                'role_id' => (int) $role->id,
                'permissions' => $validNames,
            ],
        ]);
    }
}