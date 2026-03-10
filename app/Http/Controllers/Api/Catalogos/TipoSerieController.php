<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\TipoSerieStoreRequest;
use App\Http\Requests\Catalogos\TipoSerieUpdateRequest;
use App\Models\TipoSerie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoSerieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TipoSerie::query()->orderBy('nombre');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nombre', 'like', "%{$q}%")
                    ->orWhere('clave', 'like', "%{$q}%");
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
            'message' => 'Tipos de serie obtenidos correctamente.',
            'data' => $query->get(),
        ]);
    }

    public function show(TipoSerie $tipo_serie): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Tipo de serie obtenido correctamente.',
            'data' => $tipo_serie,
        ]);
    }

    public function store(TipoSerieStoreRequest $request): JsonResponse
    {
        $tipoSerie = TipoSerie::create([
            'nombre' => trim($request->nombre),
            'clave' => strtoupper(trim($request->clave)),
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de serie creado correctamente.',
            'data' => $tipoSerie,
        ], 201);
    }

    public function update(TipoSerieUpdateRequest $request, TipoSerie $tipo_serie): JsonResponse
    {
        $tipo_serie->update([
            'nombre' => trim($request->nombre),
            'clave' => strtoupper(trim($request->clave)),
            'activo' => $request->boolean('activo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de serie actualizado correctamente.',
            'data' => $tipo_serie->fresh(),
        ]);
    }

    public function destroy(TipoSerie $tipo_serie): JsonResponse
    {
        $tipo_serie->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de serie eliminado correctamente.',
        ]);
    }
}