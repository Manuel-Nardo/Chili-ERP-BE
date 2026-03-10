<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\SerieSucursalStoreRequest;
use App\Http\Requests\Catalogos\SerieSucursalUpdateRequest;
use App\Models\SerieSucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SerieSucursalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SerieSucursal::query()
            ->with([
                'cliente:id,nombre',
                'tipoSerie:id,nombre,clave,activo',
            ])
            ->orderBy('serie');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('serie', 'like', "%{$q}%")
                    ->orWhereHas('cliente', function ($clienteQuery) use ($q) {
                        $clienteQuery->where('nombre', 'like', "%{$q}%");
                    })
                    ->orWhereHas('tipoSerie', function ($tipoQuery) use ($q) {
                        $tipoQuery->where('nombre', 'like', "%{$q}%")
                            ->orWhere('clave', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->has('activo') && $request->activo !== '') {
            $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($activo)) {
                $query->where('activo', $activo);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Series por cliente obtenidas correctamente.',
            'data' => $query->get(),
        ]);
    }

    public function show(SerieSucursal $serie_sucursal): JsonResponse
    {
        $serie_sucursal->load([
            'cliente:id,nombre',
            'tipoSerie:id,nombre,clave,activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Serie por cliente obtenida correctamente.',
            'data' => $serie_sucursal,
        ]);
    }

    public function store(SerieSucursalStoreRequest $request): JsonResponse
    {
        $serieSucursal = SerieSucursal::create([
            'cliente_id' => $request->cliente_id,
            'tipo_serie_id' => $request->tipo_serie_id,
            'serie' => strtoupper(trim($request->serie)),
            'folio_actual' => $request->input('folio_actual', 0),
            'activo' => $request->boolean('activo', true),
        ]);

        $serieSucursal->load([
            'cliente:id,nombre',
            'tipoSerie:id,nombre,clave,activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Serie por cliente creada correctamente.',
            'data' => $serieSucursal,
        ], 201);
    }

    public function update(SerieSucursalUpdateRequest $request, SerieSucursal $serie_sucursal): JsonResponse
    {
        $serie_sucursal->update([
            'cliente_id' => $request->cliente_id,
            'tipo_serie_id' => $request->tipo_serie_id,
            'serie' => strtoupper(trim($request->serie)),
            'folio_actual' => $request->folio_actual,
            'activo' => $request->boolean('activo'),
        ]);

        $serie_sucursal->load([
            'cliente:id,nombre',
            'tipoSerie:id,nombre,clave,activo',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Serie por cliente actualizada correctamente.',
            'data' => $serie_sucursal->fresh(),
        ]);
    }

    public function destroy(SerieSucursal $serie_sucursal): JsonResponse
    {
        $serie_sucursal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Serie por cliente eliminada correctamente.',
        ]);
    }
}