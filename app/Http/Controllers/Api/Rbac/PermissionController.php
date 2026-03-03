<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\PermissionStoreRequest;
use App\Http\Requests\Rbac\PermissionUpdateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    private string $guard = 'web';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $items = Permission::query()
            ->where('guard_name', $this->guard)
            ->when($q !== '', fn ($qr) => $qr->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(PermissionStoreRequest $request)
    {
        $name = (string) $request->input('name');

        try {
            $perm = Permission::create([
                'name' => $name,
                'guard_name' => $this->guard,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (int) $perm->id,
                    'name' => $perm->name,
                    'guard_name' => $perm->guard_name,
                ],
            ], 201);

        } catch (PermissionAlreadyExists $e) {
            return response()->json([
                'success' => false,
                'message' => "El permiso '{$name}' ya existe para el guard '{$this->guard}'.",
                'code' => 'PERMISSION_ALREADY_EXISTS',
                'errors' => [
                    'name' => ["Ya existe un permiso con ese nombre para el guard '{$this->guard}'."],
                ],
            ], 409);
        }
    }

    public function update(PermissionUpdateRequest $request, Permission $permission)
    {
        abort_if($permission->guard_name !== $this->guard, 404);

        $name = (string) $request->input('name', $permission->name);

        try {
            if ($request->filled('name')) {
                $permission->name = $name;
                $permission->save();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (int) $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ],
            ]);

        } catch (PermissionAlreadyExists $e) {
            return response()->json([
                'success' => false,
                'message' => "El permiso '{$name}' ya existe para el guard '{$this->guard}'.",
                'code' => 'PERMISSION_ALREADY_EXISTS',
                'errors' => [
                    'name' => ["Ya existe un permiso con ese nombre para el guard '{$this->guard}'."],
                ],
            ], 409);
        }
    }

    public function destroy(Permission $permission)
    {
        abort_if($permission->guard_name !== $this->guard, 404);

        // Regla opcional: no borrar permisos "core"
        // if (str_starts_with($permission->name, 'rbac.')) { ... }

        // Regla recomendada: si está asignado a roles, bloquear
        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un permiso asignado a uno o más roles.',
                'code' => 'PERMISSION_IN_USE',
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permiso eliminado.',
        ]);
    }
}