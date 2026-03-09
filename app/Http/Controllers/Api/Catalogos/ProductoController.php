<?php

namespace App\Http\Controllers\Api\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogos\ProductoStoreRequest;
use App\Http\Requests\Catalogos\ProductoUpdateRequest;
use App\Models\Producto;
use App\Models\ProductoCosto;
use App\Models\ProductoPrecio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Producto::query()
            ->with([
                'linea:id,nombre',
                'tipoPedido:id,nombre',
                'medida:id,nombre,abreviatura',
                'medidaCompra:id,nombre,abreviatura',
                'impuestos:id,nombre,codigo,tipo,porcentaje',
            ])
            ->orderBy('nombre');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nombre', 'like', "%{$q}%")
                    ->orWhere('clave', 'like', "%{$q}%")
                    ->orWhere('clave_sat', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            });
        }

        if ($request->filled('linea_id')) {
            $query->where('linea_id', $request->linea_id);
        }

        if ($request->filled('tipo_pedido_id')) {
            $query->where('tipo_pedido_id', $request->tipo_pedido_id);
        }

        if ($request->filled('ruta')) {
            $query->where('ruta', strtoupper(trim((string) $request->ruta)));
        }

        if ($request->has('activo') && $request->activo !== '') {
            $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($activo)) {
                $query->where('activo', $activo);
            }
        }

        if ($request->has('facturable') && $request->facturable !== '') {
            $facturable = filter_var($request->facturable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($facturable)) {
                $query->where('facturable', $facturable);
            }
        }

        $perPage = (int) $request->get('per_page', 50);

        if ($request->boolean('paginate', false)) {
            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos correctamente.',
                'data' => $query->paginate($perPage),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Productos obtenidos correctamente.',
            'data' => $query->get(),
        ]);
    }

    public function show(Producto $producto): JsonResponse
    {
        $producto->load([
            'linea:id,nombre',
            'tipoPedido:id,nombre',
            'medida:id,nombre,abreviatura',
            'medidaCompra:id,nombre,abreviatura',
            'impuestos:id,nombre,codigo,tipo,porcentaje',
            'costos' => fn ($q) => $q->orderByDesc('fecha_inicio'),
            'precios' => fn ($q) => $q->orderByDesc('fecha_inicio'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto obtenido correctamente.',
            'data' => $producto,
        ]);
    }

    public function store(ProductoStoreRequest $request): JsonResponse
    {
        $producto = DB::transaction(function () use ($request) {
            $producto = Producto::create([
                'clave' => $request->clave,
                'clave_sat' => $request->filled('clave_sat') ? trim($request->clave_sat) : null,
                'nombre' => trim($request->nombre),
                'descripcion' => $request->filled('descripcion') ? trim($request->descripcion) : null,
                'activo' => $request->boolean('activo', true),
                'facturable' => $request->boolean('facturable', false),
                'linea_id' => $request->linea_id,
                'tipo_pedido_id' => $request->tipo_pedido_id,
                'medida_id' => $request->medida_id,
                'medida_compra_id' => $request->medida_compra_id,
                'ruta' => $request->filled('ruta') ? strtoupper(trim($request->ruta)) : null,
                'precio_actual' => $request->filled('precio_actual') ? $request->precio_actual : null,
                'costo_actual' => $request->filled('costo_actual') ? $request->costo_actual : null,
            ]);

            if ($request->filled('impuestos')) {
                $producto->impuestos()->sync($request->impuestos);
            }

            if ($request->filled('precio_actual')) {
                ProductoPrecio::create([
                    'producto_id' => $producto->id,
                    'precio' => $request->precio_actual,
                    'fecha_inicio' => $request->input('fecha_inicio_precio', now()->toDateString()),
                    'fecha_fin' => null,
                    'motivo' => $request->input('motivo_precio'),
                ]);
            }

            if ($request->filled('costo_actual')) {
                ProductoCosto::create([
                    'producto_id' => $producto->id,
                    'costo' => $request->costo_actual,
                    'fecha_inicio' => $request->input('fecha_inicio_costo', now()->toDateString()),
                    'fecha_fin' => null,
                    'motivo' => $request->input('motivo_costo'),
                ]);
            }

            return $producto->load([
                'linea:id,nombre',
                'tipoPedido:id,nombre',
                'medida:id,nombre,abreviatura',
                'medidaCompra:id,nombre,abreviatura',
                'impuestos:id,nombre,codigo,tipo,porcentaje',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $producto,
        ], 201);
    }

    public function update(ProductoUpdateRequest $request, Producto $producto): JsonResponse
    {
        $producto = DB::transaction(function () use ($request, $producto) {
            $precioAnterior = $producto->precio_actual !== null ? (float) $producto->precio_actual : null;
            $costoAnterior = $producto->costo_actual !== null ? (float) $producto->costo_actual : null;

            $nuevoPrecio = $request->filled('precio_actual') ? (float) $request->precio_actual : null;
            $nuevoCosto = $request->filled('costo_actual') ? (float) $request->costo_actual : null;

            $producto->update([
                'clave' => $request->clave,
                'clave_sat' => $request->filled('clave_sat') ? trim($request->clave_sat) : null,
                'nombre' => trim($request->nombre),
                'descripcion' => $request->filled('descripcion') ? trim($request->descripcion) : null,
                'activo' => $request->boolean('activo'),
                'facturable' => $request->boolean('facturable'),
                'linea_id' => $request->linea_id,
                'tipo_pedido_id' => $request->tipo_pedido_id,
                'medida_id' => $request->medida_id,
                'medida_compra_id' => $request->medida_compra_id,
                'ruta' => $request->filled('ruta') ? strtoupper(trim($request->ruta)) : null,
                'precio_actual' => $nuevoPrecio,
                'costo_actual' => $nuevoCosto,
            ]);

            $producto->impuestos()->sync($request->input('impuestos', []));

            if ($request->filled('precio_actual') && ($precioAnterior === null || $precioAnterior !== $nuevoPrecio)) {
                ProductoPrecio::where('producto_id', $producto->id)
                    ->whereNull('fecha_fin')
                    ->update([
                        'fecha_fin' => now()->toDateString(),
                    ]);

                ProductoPrecio::create([
                    'producto_id' => $producto->id,
                    'precio' => $nuevoPrecio,
                    'fecha_inicio' => $request->input('fecha_inicio_precio', now()->toDateString()),
                    'fecha_fin' => null,
                    'motivo' => $request->input('motivo_precio'),
                ]);
            }

            if ($request->filled('costo_actual') && ($costoAnterior === null || $costoAnterior !== $nuevoCosto)) {
                ProductoCosto::where('producto_id', $producto->id)
                    ->whereNull('fecha_fin')
                    ->update([
                        'fecha_fin' => now()->toDateString(),
                    ]);

                ProductoCosto::create([
                    'producto_id' => $producto->id,
                    'costo' => $nuevoCosto,
                    'fecha_inicio' => $request->input('fecha_inicio_costo', now()->toDateString()),
                    'fecha_fin' => null,
                    'motivo' => $request->input('motivo_costo'),
                ]);
            }

            return $producto->fresh()->load([
                'linea:id,nombre',
                'tipoPedido:id,nombre',
                'medida:id,nombre,abreviatura',
                'medidaCompra:id,nombre,abreviatura',
                'impuestos:id,nombre,codigo,tipo,porcentaje',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $producto,
        ]);
    }

    public function destroy(Producto $producto): JsonResponse
    {
        DB::transaction(function () use ($producto) {
            $producto->impuestos()->detach();
            $producto->costos()->delete();
            $producto->precios()->delete();
            $producto->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }
}