<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rbac\UserStoreRequest;
use App\Http\Requests\Rbac\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private string $guard = 'web';

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $withRoles = filter_var($request->query('with_roles', true), FILTER_VALIDATE_BOOLEAN);

        $perPage = (int) $request->query('per_page', 0);

        $query = User::query()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name');

        if ($withRoles) {
            $query->with(['roles:id,name,guard_name']);
        }

        $result = $perPage > 0
            ? $query->paginate($perPage, ['id','name','email'])
            : $query->get(['id','name','email']);

        $map = function (User $u) use ($withRoles) {
            return [
                'id' => (int) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $withRoles ? $u->roles->pluck('name')->values() : null,
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

    public function show(Request $request, User $user)
    {
        $withRoles = filter_var($request->query('with_roles', true), FILTER_VALIDATE_BOOLEAN);

        if ($withRoles) $user->load(['roles:id,name,guard_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $withRoles ? $user->roles->pluck('name')->values() : [],
            ],
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        if (!empty($data['roles'])) {
            $roles = Role::query()
                ->where('guard_name', $this->guard)
                ->whereIn('name', array_values(array_unique($data['roles'])))
                ->pluck('name')
                ->all();

            $user->syncRoles($roles);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->values(),
            ],
        ], 201);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        if (array_key_exists('name', $data)) $user->name = $data['name'];
        if (array_key_exists('email', $data)) $user->email = $data['email'];
        if (!empty($data['password'])) $user->password = Hash::make($data['password']);
        $user->save();

        if (array_key_exists('roles', $data)) {
            $roles = Role::query()
                ->where('guard_name', $this->guard)
                ->whereIn('name', array_values(array_unique($data['roles'] ?? [])))
                ->pluck('name')
                ->all();

            $user->syncRoles($roles);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->values(),
            ],
        ]);
    }

    public function destroy(User $user)
    {
        // regla opcional: no borrar tu super admin por id/email
        // if ($user->email === 'admin@...') ...

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado.',
        ]);
    }
}