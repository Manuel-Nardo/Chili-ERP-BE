<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas.',
                'errors'  => [
                    'email' => ['Credenciales inválidas.'],
                ],
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Opcional: mantener 1 token activo por usuario (descomenta si lo quieres)
        // $user->tokens()->delete();

        $token = $user->createToken('web-admin')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'sucursal_id'=> $user->sucursal_id ?? null,
            ],
            'roles'       => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ]);
    }
}