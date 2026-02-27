<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;

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