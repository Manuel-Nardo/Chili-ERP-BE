<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\ImpuestoStoreRequest;
use App\Http\Requests\Catalogos\ImpuestoUpdateRequest;
use App\Models\Impuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImpuestoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Impuesto::query()->orderBy('nombre');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nombre', 'like', "%{$q}%")
                    ->orWhere('codigo', 'like', "%{$q}%")
                    ->orWhere('tipo', 'like', "%{$q}%")
                    ->orWhere('porcentaje', 'like', "%{$q}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', strtoupper(trim((string) $request->tipo)));
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
                'message' => 'Impuestos obtenidos correctamente.',
                'data' => $query->paginate($perPage),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Impuestos obtenidos correctamente.',
            'data' => $query->get(),
        ]);
    }

    public function show(Impuesto $impuesto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Impuesto obtenido correctamente.',
            'data' => $impuesto,
        ]);
    }

    public function store(ImpuestoStoreRequest $request): JsonResponse
    {
        $impuesto = Impuesto::create([
            'nombre' => trim($request->nombre),
            'codigo' => strtoupper(trim($request->codigo)),
            'tipo' => strtoupper(trim($request->tipo)),
            'porcentaje' => $request->porcentaje,
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Impuesto creado correctamente.',
            'data' => $impuesto,
        ], 201);
    }

    public function update(ImpuestoUpdateRequest $request, Impuesto $impuesto): JsonResponse
    {
        $impuesto->update([
            'nombre' => trim($request->nombre),
            'codigo' => strtoupper(trim($request->codigo)),
            'tipo' => strtoupper(trim($request->tipo)),
            'porcentaje' => $request->porcentaje,
            'activo' => $request->boolean('activo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Impuesto actualizado correctamente.',
            'data' => $impuesto->fresh(),
        ]);
    }

    public function destroy(Impuesto $impuesto): JsonResponse
    {
        $impuesto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Impuesto eliminado correctamente.',
        ]);
    }
}