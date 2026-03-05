<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\ZonaStoreRequest;
use App\Http\Requests\Catalogos\ZonaUpdateRequest;
use App\Models\Zona;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activo = $request->query('activo', null);

        $query = Zona::query();

        if ($q !== '') $query->where('nombre', 'like', "%{$q}%");
        if ($activo !== null) $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOL));

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nombre')->paginate((int) $request->query('per_page', 15)),
        ]);
    }

    public function store(ZonaStoreRequest $request)
    {
        $zona = Zona::create($request->validated());

        return response()->json(['success' => true, 'data' => $zona], 201);
    }

    public function show(Zona $zona)
    {
        return response()->json(['success' => true, 'data' => $zona]);
    }

    public function update(ZonaUpdateRequest $request, Zona $zona)
    {
        $zona->update($request->validated());

        return response()->json(['success' => true, 'data' => $zona->fresh()]);
    }

    public function destroy(Zona $zona)
    {
        $zona->delete();

        return response()->json(['success' => true]);
    }
}