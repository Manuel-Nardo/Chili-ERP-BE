<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\LineaStoreRequest;
use App\Http\Requests\Catalogos\LineaUpdateRequest;
use App\Models\Linea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LineaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Linea::query()->orderBy('nombre');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nombre', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        if ($request->has('activo') && $request->activo !== '') {
            $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($activo)) {
                $query->where('activo', $activo);
            }
        }

        $perPage = (int) $request->get('per_page', 50);

        if ($request->boolean('paginate', false)) {
            return response()->json([
                'success' => true,
                'message' => 'Líneas obtenidas correctamente.',
                'data' => $query->paginate($perPage),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Líneas obtenidas correctamente.',
            'data' => $query->get(),
        ]);
    }

    public function show(Linea $linea): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Línea obtenida correctamente.',
            'data' => $linea,
        ]);
    }

    public function store(LineaStoreRequest $request): JsonResponse
    {
        $linea = Linea::create([
            'nombre' => trim($request->nombre),
            'descripcion' => $request->filled('descripcion') ? trim($request->descripcion) : null,
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Línea creada correctamente.',
            'data' => $linea,
        ], 201);
    }

    public function update(LineaUpdateRequest $request, Linea $linea): JsonResponse
    {
        $linea->update([
            'nombre' => trim($request->nombre),
            'descripcion' => $request->filled('descripcion') ? trim($request->descripcion) : null,
            'activo' => $request->boolean('activo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Línea actualizada correctamente.',
            'data' => $linea->fresh(),
        ]);
    }

    public function destroy(Linea $linea): JsonResponse
    {
        $linea->delete();

        return response()->json([
            'success' => true,
            'message' => 'Línea eliminada correctamente.',
        ]);
    }
}