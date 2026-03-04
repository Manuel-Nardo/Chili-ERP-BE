<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\RoleStoreRequest;
use App\Http\Requests\Rbac\RoleUpdateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private string $guard = 'web';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $withPermissions = filter_var($request->query('with_permissions', false), FILTER_VALIDATE_BOOLEAN);
        $withUsersCount  = filter_var($request->query('with_users_count', true), FILTER_VALIDATE_BOOLEAN);

        $perPage = (int) $request->query('per_page', 0);

        $query = Role::query()
            ->where('guard_name', $this->guard)
            ->when($q !== '', fn ($qr) => $qr->where('name', 'like', "%{$q}%"))
            ->orderBy('name');

        if ($withPermissions) {
            $query->with(['permissions:id,name,guard_name']);
        }

        if ($withUsersCount) {
            $query->withCount('users');
        }

        $result = $perPage > 0
            ? $query->paginate($perPage, ['id', 'name', 'guard_name'])
            : $query->get(['id', 'name', 'guard_name']);

        $map = function (Role $role) use ($withPermissions, $withUsersCount) {
            return [
                'id' => (int) $role->id,
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
        try {
            $role = Role::create([
                'name' => $request->input('name'),
                'guard_name' => $this->guard,
            ]);

        } catch (RoleAlreadyExists $e) {
            return response()->json([
                'success' => false,
                'message' => "El rol '{$request->input('name')}' ya existe para el guard '{$this->guard}'.",
                'code' => 'ROLE_ALREADY_EXISTS',
                'errors' => [
                    'name' => ["Ya existe un rol con ese nombre para el guard '{$this->guard}'."],
                ],
            ], 409);
        }

        // Solo asigna permisos si explícitamente mandas el campo
        if ($request->has('permissions')) {
            $permissions = (array) $request->input('permissions', []);
            $permIds = $this->resolvePermissionIds($permissions);
            $role->syncPermissions($permIds);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'users_count' => 0,
            ],
        ], 201);
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        abort_if($role->guard_name !== $this->guard, 404);

        try {
            if ($request->filled('name')) {
                $role->name = $request->input('name');
                $role->save();
            }

        } catch (RoleAlreadyExists $e) {
            return response()->json([
                'success' => false,
                'message' => "El rol '{$request->input('name')}' ya existe para el guard '{$this->guard}'.",
                'code' => 'ROLE_ALREADY_EXISTS',
                'errors' => [
                    'name' => ["Ya existe un rol con ese nombre para el guard '{$this->guard}'."],
                ],
            ], 409);
        }

        if ($request->has('permissions')) {
            $permissions = (array) $request->input('permissions', []);
            $permIds = $this->resolvePermissionIds($permissions);
            $role->syncPermissions($permIds);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
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

    public function show(Request $request, Role $role)
    {
        abort_if($role->guard_name !== $this->guard, 404);

        $withPermissions = filter_var($request->query('with_permissions', true), FILTER_VALIDATE_BOOLEAN);

        if ($withPermissions) {
            $role->load(['permissions:id,name,guard_name']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $withPermissions
                    ? $role->permissions->pluck('name')->values()
                    : [],
            ],
        ]);
    }
}