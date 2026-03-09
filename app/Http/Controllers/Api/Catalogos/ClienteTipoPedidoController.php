<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\ClienteTipoPedidoStoreRequest;
use App\Http\Requests\Catalogos\ClienteTipoPedidoUpdateRequest;
use App\Models\ClienteTipoPedido;
use Illuminate\Http\Request;

class ClienteTipoPedidoController extends Controller
{
    public function index(Request $request)
    {
        $clienteId = $request->query('cliente_id');
        $tipoPedidoId = $request->query('tipo_pedido_id');
        $usarDefault = $request->query('usar_horario_default', null);
        $activo = $request->query('activo', null);
        $perPage = (int) $request->query('per_page', 50);

        $query = ClienteTipoPedido::query()
            ->with([
                'cliente',
                'tipoPedido',
                'horarios',
            ]);

        if ($clienteId) {
            $query->where('cliente_id', (int) $clienteId);
        }

        if ($tipoPedidoId) {
            $query->where('tipo_pedido_id', (int) $tipoPedidoId);
        }

        if ($usarDefault !== null) {
            $query->where('usar_horario_default', filter_var($usarDefault, FILTER_VALIDATE_BOOL));
        }

        if ($activo !== null) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->orderBy('cliente_id')
                ->orderBy('tipo_pedido_id')
                ->paginate($perPage),
        ]);
    }

    public function store(ClienteTipoPedidoStoreRequest $request)
    {
        $validated = $request->validated();

        $exists = ClienteTipoPedido::query()
            ->where('cliente_id', $validated['cliente_id'])
            ->where('tipo_pedido_id', $validated['tipo_pedido_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ese tipo de pedido ya está asignado a la sucursal seleccionada.',
            ], 422);
        }

        $asignacion = ClienteTipoPedido::create([
            'cliente_id' => $validated['cliente_id'],
            'tipo_pedido_id' => $validated['tipo_pedido_id'],
            'usar_horario_default' => $validated['usar_horario_default'] ?? true,
            'activo' => $validated['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $asignacion->load(['cliente', 'tipoPedido', 'horarios']),
        ], 201);
    }

    public function show(ClienteTipoPedido $cliente_tipo_pedido)
    {
        return response()->json([
            'success' => true,
            'data' => $cliente_tipo_pedido->load(['cliente', 'tipoPedido', 'horarios']),
        ]);
    }

    public function update(ClienteTipoPedidoUpdateRequest $request, ClienteTipoPedido $cliente_tipo_pedido)
    {
        $validated = $request->validated();

        $clienteId = $validated['cliente_id'] ?? $cliente_tipo_pedido->cliente_id;
        $tipoPedidoId = $validated['tipo_pedido_id'] ?? $cliente_tipo_pedido->tipo_pedido_id;

        $exists = ClienteTipoPedido::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo_pedido_id', $tipoPedidoId)
            ->where('id', '!=', $cliente_tipo_pedido->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ese tipo de pedido ya está asignado a la sucursal seleccionada.',
            ], 422);
        }

        $cliente_tipo_pedido->update($validated);

        return response()->json([
            'success' => true,
            'data' => $cliente_tipo_pedido->fresh()->load(['cliente', 'tipoPedido', 'horarios']),
        ]);
    }

    public function destroy(ClienteTipoPedido $cliente_tipo_pedido)
    {
        $cliente_tipo_pedido->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}