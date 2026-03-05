<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\TipoClienteStoreRequest;
use App\Http\Requests\Catalogos\TipoClienteUpdateRequest;
use App\Models\TipoCliente;
use Illuminate\Http\Request;

class TipoClienteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activo = $request->query('activo', null);

        $query = TipoCliente::query();

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('nombre', 'like', "%{$q}%")
                   ->orWhere('clave', 'like', "%{$q}%");
            });
        }

        if ($activo !== null) $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nombre')->paginate((int) $request->query('per_page', 15)),
        ]);
    }

    public function store(TipoClienteStoreRequest $request)
    {
        $tipo = TipoCliente::create($request->validated());

        return response()->json(['success' => true, 'data' => $tipo], 201);
    }

    public function show(TipoCliente $tipos_cliente)
    {
        return response()->json(['success' => true, 'data' => $tipos_cliente]);
    }

    public function update(TipoClienteUpdateRequest $request, TipoCliente $tipos_cliente)
    {
        $tipos_cliente->update($request->validated());

        return response()->json(['success' => true, 'data' => $tipos_cliente->fresh()]);
    }

    public function destroy(TipoCliente $tipos_cliente)
    {
        $tipos_cliente->delete();

        return response()->json(['success' => true]);
    }
}