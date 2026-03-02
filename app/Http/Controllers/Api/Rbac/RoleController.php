<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\RoleStoreRequest;
use App\Http\Requests\Rbac\RoleUpdateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private string $guard = 'web'; // ✅ fijo

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $withPermissions = filter_var($request->query('with_permissions', true), FILTER_VALIDATE_BOOLEAN);
        $withUsersCount  = filter_var($request->query('with_users_count', false), FILTER_VALIDATE_BOOLEAN); // ✅ default false
        $perPage = (int) $request->query('per_page', 0); // 0 = sin paginar

        $query = Role::query()
            ->where('guard_name', $this->guard)
            ->when($q !== '', fn ($qr) => $qr->where('name', 'like', "%{$q}%"))
            ->orderBy('name');

        if ($withPermissions) $query->with(['permissions:id,name']);
        if ($withUsersCount)  $query->withCount('users'); // solo si lo pides

        $result = $perPage > 0
            ? $query->paginate($perPage)
            : $query->get(['id', 'name', 'guard_name']);

        $map = function (Role $role) use ($withPermissions, $withUsersCount) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'users_count' => $withUsersCount ? (int) ($role->users_count ?? 0) : null,
                'permissions' => $withPermissions
                    ? $role->permissions->pluck('name')->values()
                    : null,
            ];
        };

        if ($perPage > 0) {
            return response()->json([
                'success' => true,
                'data' => collect($result->items())->map($map)->values(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'last_page' => $result->lastPage(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $result->map($map)->values(),
        ]);
    }

    public function store(RoleStoreRequest $request)
    {
        $role = Role::create([
            'name' => $request->input('name'),
            'guard_name' => $this->guard, // ✅ fijo
        ]);

        $permissions = (array) $request->input('permissions', []);
        $permIds = $this->resolvePermissionIds($permissions);

        $role->syncPermissions($permIds);
        $role->load(['permissions:id,name']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users_count' => 0,
            ],
        ], 201);
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        // ✅ seguridad: evita editar roles de otro guard
        abort_if($role->guard_name !== $this->guard, 404);

        if ($request->filled('name')) {
            $role->name = $request->input('name');
            $role->save();
        }

        if ($request->has('permissions')) {
            $permissions = (array) $request->input('permissions', []);
            $permIds = $this->resolvePermissionIds($permissions);
            $role->syncPermissions($permIds);
        }

        $role->load(['permissions:id,name']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
        ]);
    }

    public function destroy(Role $role)
    {
        abort_if($role->guard_name !== $this->guard, 404);

        if ($role->name === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el rol super_admin.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado.',
        ]);
    }

    /**
     * Recibe permissions como:
     * - ["users.view","roles.create"] (names)
     * - [1,2,3] (ids)
     */
    private function resolvePermissionIds(array $permissions): array
    {
        if (empty($permissions)) return [];

        $allNumeric = collect($permissions)->every(fn ($p) => is_int($p) || ctype_digit((string) $p));

        $q = Permission::query()->where('guard_name', $this->guard);

        return $allNumeric
            ? $q->whereIn('id', $permissions)->pluck('id')->values()->all()
            : $q->whereIn('name', $permissions)->pluck('id')->values()->all();
    }
}