<?php

namespace App\Http\Controllers\Api\Pedidos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pedidos\IndexPedidoErpRequest;
use App\Models\PedidoErp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PedidoErpController extends Controller
{
    public function index(IndexPedidoErpRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = PedidoErp::query()
            ->with([
                'detalles.producto',
                'serieSucursal',
                'tipoPedido',
                'sucursalOrigen',
                'sucursalDestino',
                'sugerencia',
            ])
            ->leftJoin('series_sucursal', 'series_sucursal.id', '=', 'pedidos_erp.serie_id')
            ->select('pedidos_erp.*');

        if (!empty($filters['estatus'])) {
            $query->where('pedidos_erp.estatus', $filters['estatus']);
        }

        if (!empty($filters['tipo_pedido_id'])) {
            $query->where('pedidos_erp.tipo_pedido_id', (int) $filters['tipo_pedido_id']);
        }

        if (!empty($filters['sucursal_destino_id'])) {
            $query->where('pedidos_erp.sucursal_destino_id', (int) $filters['sucursal_destino_id']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('pedidos_erp.fecha_pedido', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('pedidos_erp.fecha_pedido', '<=', $filters['fecha_hasta']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('pedidos_erp.id', 'LIKE', "%{$search}%")
                    ->orWhere('pedidos_erp.folio', 'LIKE', "%{$search}%")
                    ->orWhere('series_sucursal.serie', 'LIKE', "%{$search}%")
                    ->orWhereRaw(
                        "CONCAT(COALESCE(series_sucursal.serie, ''), ' - ', COALESCE(pedidos_erp.folio, '')) LIKE ?",
                        ["%{$search}%"]
                    )
                    ->orWhereHas('sucursalDestino', function ($q2) use ($search) {
                        $q2->where('nombre', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('tipoPedido', function ($q2) use ($search) {
                        $q2->where('nombre', 'LIKE', "%{$search}%");
                    });
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        $items = $query
            ->orderByDesc('pedidos_erp.id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Pedidos ERP obtenidos correctamente.',
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function remisionable(PedidoErp $pedidoErp): JsonResponse
    {
        $pedidoErp->load([
            'detalles.producto',
            'serieSucursal',
            'tipoPedido',
            'sucursalOrigen',
            'sucursalDestino',
        ]);

        $remisionadoPorDetalle = DB::table('remisiones_det_erp as rde')
            ->join('remisiones_erp as re', 're.id', '=', 'rde.remision_id')
            ->where('re.pedido_erp_id', $pedidoErp->id)
            ->select(
                'rde.pedido_det_erp_id',
                DB::raw('SUM(rde.cantidad) as total_remisionado')
            )
            ->groupBy('rde.pedido_det_erp_id')
            ->pluck('total_remisionado', 'rde.pedido_det_erp_id');

        $detalles = collect($pedidoErp->detalles)->map(function ($detalle) use ($remisionadoPorDetalle) {
            $detalleId = (int) $detalle->id;
            $cantidadPedida = (float) ($detalle->cantidad ?? 0);
            $cantidadRemisionada = (float) ($remisionadoPorDetalle[$detalleId] ?? 0);
            $cantidadPendiente = max(0, $cantidadPedida - $cantidadRemisionada);

            return [
                'pedido_det_erp_id' => $detalleId,
                'articulo_id' => (int) $detalle->articulo_id,
                'producto' => [
                    'id' => $detalle->producto?->id,
                    'nombre' => $detalle->producto?->nombre,
                    'clave' => $detalle->producto?->clave,
                ],
                'cantidad_pedida' => $cantidadPedida,
                'cantidad_remisionada' => $cantidadRemisionada,
                'cantidad_pendiente' => $cantidadPendiente,
                'precio_unitario' => (float) ($detalle->precio_unitario ?? 0),
                'importe' => (float) ($detalle->importe ?? 0),
                'iva' => (float) ($detalle->iva ?? 0),
                'impuesto_iva' => (float) ($detalle->impuesto_iva ?? 0),
                'total' => (float) ($detalle->total ?? 0),
                'estatus' => $detalle->estatus,
                'observaciones' => $detalle->observaciones,
            ];
        });

        $totales = [
            'partidas' => $detalles->count(),
            'cantidad_pedida' => (float) $detalles->sum('cantidad_pedida'),
            'cantidad_remisionada' => (float) $detalles->sum('cantidad_remisionada'),
            'cantidad_pendiente' => (float) $detalles->sum('cantidad_pendiente'),
            'completo' => $detalles->count() > 0
                ? $detalles->every(fn ($item) => (float) $item['cantidad_pendiente'] <= 0)
                : false,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Resumen remisionable obtenido correctamente.',
            'data' => [
                'pedido' => [
                    'id' => $pedidoErp->id,
                    'serie_id' => $pedidoErp->serie_id,
                    'folio' => $pedidoErp->folio,
                    'estatus' => $pedidoErp->estatus,
                    'fecha_pedido' => $pedidoErp->fecha_pedido,
                    'fecha_objetivo' => $pedidoErp->fecha_objetivo,
                    'subtotal' => (float) $pedidoErp->subtotal,
                    'impuestos' => (float) $pedidoErp->impuestos,
                    'total' => (float) $pedidoErp->total,
                    'tipo_pedido' => $pedidoErp->tipoPedido ? [
                        'id' => $pedidoErp->tipoPedido->id,
                        'nombre' => $pedidoErp->tipoPedido->nombre,
                    ] : null,
                    'sucursal_origen' => $pedidoErp->sucursalOrigen ? [
                        'id' => $pedidoErp->sucursalOrigen->id,
                        'nombre' => $pedidoErp->sucursalOrigen->nombre,
                    ] : null,
                    'sucursal_destino' => $pedidoErp->sucursalDestino ? [
                        'id' => $pedidoErp->sucursalDestino->id,
                        'nombre' => $pedidoErp->sucursalDestino->nombre,
                    ] : null,
                    'serie_sucursal' => $pedidoErp->serieSucursal ? [
                        'id' => $pedidoErp->serieSucursal->id,
                        'serie' => $pedidoErp->serieSucursal->serie,
                    ] : null,
                ],
                'totales' => $totales,
                'detalles' => $detalles->values(),
            ],
        ]);
    }

    public function show(PedidoErp $pedidoErp): JsonResponse
    {
        $pedidoErp->load([
            'detalles.producto',
            'serieSucursal',
            'tipoPedido',
            'sucursalOrigen',
            'sucursalDestino',
            'sugerencia',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido ERP obtenido correctamente.',
            'data' => $pedidoErp,
        ]);
    }
}