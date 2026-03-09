<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\UnidadStoreRequest;
use App\Http\Requests\Catalogos\UnidadUpdateRequest;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnidadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Unidad::query()->orderBy('nombre');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('clave', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('abreviatura', 'like', "%{$q}%");
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
                'message' => 'Unidades obtenidas correctamente.',
                'data' => $query->paginate($perPage),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Unidades obtenidas correctamente.',
            'data' => $query->get(),
        ]);
    }

    public function show(Unidad $unidad): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Unidad obtenida correctamente.',
            'data' => $unidad,
        ]);
    }

    public function store(UnidadStoreRequest $request): JsonResponse
    {
        $unidad = Unidad::create([
            'clave' => strtoupper(trim($request->clave)),
            'nombre' => trim($request->nombre),
            'abreviatura' => trim($request->abreviatura),
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unidad creada correctamente.',
            'data' => $unidad,
        ], 201);
    }

    public function update(UnidadUpdateRequest $request, Unidad $unidad): JsonResponse
    {
        $unidad->update([
            'clave' => strtoupper(trim($request->clave)),
            'nombre' => trim($request->nombre),
            'abreviatura' => trim($request->abreviatura),
            'activo' => $request->boolean('activo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unidad actualizada correctamente.',
            'data' => $unidad->fresh(),
        ]);
    }

    public function destroy(Unidad $unidad): JsonResponse
    {
        $unidad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unidad eliminada correctamente.',
        ]);
    }
}