<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Rbac\RoleController;
use App\Http\Controllers\Api\Rbac\PermissionController;

use App\Http\Controllers\Api\Users\UserController;
use Spatie\Permission\Models\Role;

Route::get('/ping', fn () => response()->json(['ok' => true]));

// Auth
Route::post('/auth/login', LoginController::class);

// Protegidas por token (Bearer) con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', LogoutController::class);
    Route::get('/me', MeController::class);

    Route::middleware('permission:users.view')->get('/users', [UserController::class, 'index']);
    Route::middleware('permission:users.create')->post('/users', [UserController::class, 'store']);
    Route::middleware('permission:users.edit')->put('/users/{user}', [UserController::class, 'update']);
    Route::middleware('permission:users.delete')->delete('/users/{user}', [UserController::class, 'destroy']);

    Route::middleware('permission:users.view')->get('/roles', function () {
        return Role::query()->orderBy('name')->pluck('name');
    });
});

Route::middleware('auth:sanctum')->prefix('rbac')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
});