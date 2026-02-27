<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()
            ->with(['roles', 'sucursal'])
            ->orderBy('id', 'desc');

        if ($search = $request->query('search')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")
                   ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $q->paginate((int)($request->query('per_page', 15)))
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8'],
            'sucursal_id' => ['nullable','integer','exists:sucursales,id'],
            'roles' => ['array'],                 // ['admin','gerente_sucursal']
            'roles.*' => ['string','max:100'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'sucursal_id' => $data['sucursal_id'] ?? null,
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return response()->json(['success' => true, 'user' => $user->load(['roles','sucursal'])], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:8'],
            'sucursal_id' => ['nullable','integer','exists:sucursales,id'],
            'roles' => ['array'],
            'roles.*' => ['string','max:100'],
        ]);

        $user->name = $data['name'];
        $user->email = strtolower($data['email']);
        $user->sucursal_id = $data['sucursal_id'] ?? null;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }

        return response()->json(['success' => true, 'user' => $user->load(['roles','sucursal'])]);
    }

    public function destroy(User $user)
    {
        // Evitar borrar al último super_admin (regla mínima)
        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un super_admin.'
            ], 422);
        }

        $user->delete();

        return response()->json(['success' => true]);
    }
}