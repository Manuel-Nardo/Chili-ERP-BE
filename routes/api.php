<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Rbac\RoleController;
use App\Http\Controllers\Api\Rbac\PermissionController;
use App\Http\Controllers\Api\Rbac\RolePermissionController;
use App\Http\Controllers\Api\Rbac\UserController;
use App\Http\Controllers\Api\Catalogos\ZonaController;
use App\Http\Controllers\Api\Catalogos\TipoClienteController;
use App\Http\Controllers\Api\Catalogos\ClienteController;
use App\Http\Controllers\Api\Catalogos\TipoPedidoController;
use App\Http\Controllers\Api\Catalogos\TipoPedidoHorarioController;
use App\Http\Controllers\Api\Catalogos\ClienteTipoPedidoController;
use App\Http\Controllers\Api\Catalogos\ClienteTipoPedidoHorarioController;
use App\Http\Controllers\Api\Catalogos\UnidadController;
use App\Http\Controllers\Api\Catalogos\ImpuestoController;
use App\Http\Controllers\Api\Catalogos\LineaController;
use App\Http\Controllers\Api\Catalogos\ProductoController;

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

Route::prefix('rbac')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
    Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);

    Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'sync']);
    Route::get('/roles/{role}', [RoleController::class, 'show']);
    
});

Route::prefix('rbac')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});

Route::prefix('catalogos')->group(function () {
    Route::apiResource('zonas', ZonaController::class);
    Route::apiResource('tipos-cliente', TipoClienteController::class);
    Route::apiResource('clientes', ClienteController::class);
});

Route::prefix('catalogos')->group(function () {
    Route::get('tipos-pedido', [TipoPedidoController::class, 'index']);
    Route::get('tipos-pedido/{tipo_pedido}', [TipoPedidoController::class, 'show']);

    Route::post('tipos-pedido', [TipoPedidoController::class, 'store']);
    Route::put('tipos-pedido/{tipo_pedido}', [TipoPedidoController::class, 'update']);
    Route::delete('tipos-pedido/{tipo_pedido}', [TipoPedidoController::class, 'destroy']);
    
    Route::get('tipos-pedido-horarios', [TipoPedidoHorarioController::class, 'index']);
    Route::get('tipos-pedido-horarios/{tipo_pedido_horario}', [TipoPedidoHorarioController::class, 'show']);

    Route::post('tipos-pedido-horarios', [TipoPedidoHorarioController::class, 'store']);
    Route::put('tipos-pedido-horarios/{tipo_pedido_horario}', [TipoPedidoHorarioController::class, 'update']);
    Route::delete('tipos-pedido-horarios/{tipo_pedido_horario}', [TipoPedidoHorarioController::class, 'destroy']);

    Route::get('clientes-tipos-pedido', [ClienteTipoPedidoController::class, 'index']);
    Route::get('clientes-tipos-pedido/{cliente_tipo_pedido}', [ClienteTipoPedidoController::class, 'show']);
    Route::post('clientes-tipos-pedido', [ClienteTipoPedidoController::class, 'store']);
    Route::put('clientes-tipos-pedido/{cliente_tipo_pedido}', [ClienteTipoPedidoController::class, 'update']);
    Route::delete('clientes-tipos-pedido/{cliente_tipo_pedido}', [ClienteTipoPedidoController::class, 'destroy']);

    Route::get('clientes-tipos-pedido-horarios', [ClienteTipoPedidoHorarioController::class, 'index']);
    Route::get('clientes-tipos-pedido-horarios/{cliente_tipo_pedido_horario}', [ClienteTipoPedidoHorarioController::class, 'show']);
    Route::post('clientes-tipos-pedido-horarios', [ClienteTipoPedidoHorarioController::class, 'store']);
    Route::put('clientes-tipos-pedido-horarios/{cliente_tipo_pedido_horario}', [ClienteTipoPedidoHorarioController::class, 'update']);
    Route::delete('clientes-tipos-pedido-horarios/{cliente_tipo_pedido_horario}', [ClienteTipoPedidoHorarioController::class, 'destroy']);

    Route::get('unidades', [UnidadController::class, 'index']);
    Route::get('unidades/{unidad}', [UnidadController::class, 'show']);
    Route::post('unidades', [UnidadController::class, 'store']);
    Route::put('unidades/{unidad}', [UnidadController::class, 'update']);
    Route::delete('unidades/{unidad}', [UnidadController::class, 'destroy']);

    Route::get('impuestos', [ImpuestoController::class, 'index']);
    Route::get('impuestos/{impuesto}', [ImpuestoController::class, 'show']);
    Route::post('impuestos', [ImpuestoController::class, 'store']);
    Route::put('impuestos/{impuesto}', [ImpuestoController::class, 'update']);
    Route::delete('impuestos/{impuesto}', [ImpuestoController::class, 'destroy']);

    Route::get('lineas', [LineaController::class, 'index']);
    Route::get('lineas/{linea}', [LineaController::class, 'show']);
    Route::post('lineas', [LineaController::class, 'store']);
    Route::put('lineas/{linea}', [LineaController::class, 'update']);
    Route::delete('lineas/{linea}', [LineaController::class, 'destroy']);

    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{producto}', [ProductoController::class, 'show']);
    Route::post('productos', [ProductoController::class, 'store']);
    Route::put('productos/{producto}', [ProductoController::class, 'update']);
    Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);
    
});