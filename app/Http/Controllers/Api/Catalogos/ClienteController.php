<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\ClienteStoreRequest;
use App\Http\Requests\Catalogos\ClienteUpdateRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activo = $request->query('activo', null);
        $tipoId = $request->query('tipo_cliente_id', null);
        $zonaId = $request->query('zona_id', null);

        $query = Cliente::query()->with(['tipo', 'zona', 'back']);

        if ($q !== '') $query->where('nombre', 'like', "%{$q}%");
        if ($activo !== null) $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));
        if ($tipoId) $query->where('tipo_cliente_id', (int) $tipoId);
        if ($zonaId) $query->where('zona_id', (int) $zonaId);

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nombre')->paginate((int) $request->query('per_page', 15)),
        ]);
    }

    public function store(ClienteStoreRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data) {
            $cliente = Cliente::create([
                'nombre' => $data['nombre'],
                'activo' => $data['activo'] ?? true,
                'tipo_cliente_id' => $data['tipo_cliente_id'],
                'zona_id' => $data['zona_id'] ?? null,
            ]);

            if (!empty($data['back'])) {
                $cliente->back()->create($data['back']);
            }

            return response()->json([
                'success' => true,
                'data' => $cliente->load(['tipo', 'zona', 'back']),
            ], 201);
        });
    }

    public function show(Cliente $cliente)
    {
        return response()->json([
            'success' => true,
            'data' => $cliente->load(['tipo', 'zona', 'back']),
        ]);
    }

    public function update(ClienteUpdateRequest $request, Cliente $cliente)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($cliente, $data) {
            $cliente->update([
                'nombre' => $data['nombre'] ?? $cliente->nombre,
                'activo' => array_key_exists('activo', $data) ? $data['activo'] : $cliente->activo,
                'tipo_cliente_id' => $data['tipo_cliente_id'] ?? $cliente->tipo_cliente_id,
                'zona_id' => array_key_exists('zona_id', $data) ? $data['zona_id'] : $cliente->zona_id,
            ]);

            if (array_key_exists('back', $data)) {
                if ($data['back'] === null || $data['back'] === []) {
                    // si mandan back vacío, lo dejamos como está (no borramos)
                } else {
                    $cliente->back()->updateOrCreate(
                        ['cliente_id' => $cliente->id],
                        $data['back']
                    );
                }
            }

            return response()->json([
                'success' => true,
                'data' => $cliente->fresh()->load(['tipo', 'zona', 'back']),
            ]);
        });
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->json(['success' => true]);
    }
}