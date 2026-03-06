<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\TipoPedidoStoreRequest;
use App\Http\Requests\Catalogos\TipoPedidoUpdateRequest;
use App\Models\TipoPedido;
use Illuminate\Http\Request;

class TipoPedidoController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activo = $request->query('activo', null);
        $perPage = (int) $request->query('per_page', 15);

        $query = TipoPedido::query();

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('detalle', 'like', "%{$q}%");
            });
        }

        if ($activo !== null) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nombre')->paginate($perPage),
        ]);
    }

    public function store(TipoPedidoStoreRequest $request)
    {
        $tipoPedido = TipoPedido::create([
            'nombre' => $request->input('nombre'),
            'detalle' => $request->input('detalle'),
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json([
            'success' => true,
            'data' => $tipoPedido,
        ], 201);
    }

    public function show(TipoPedido $tipo_pedido)
    {
        return response()->json([
            'success' => true,
            'data' => $tipo_pedido,
        ]);
    }

    public function update(TipoPedidoUpdateRequest $request, TipoPedido $tipo_pedido)
    {
        $tipo_pedido->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $tipo_pedido->fresh(),
        ]);
    }

    public function destroy(TipoPedido $tipo_pedido)
    {
        $tipo_pedido->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}