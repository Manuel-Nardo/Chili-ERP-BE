<?php

namespace App\Http\Controllers\Api\Remisiones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Remisiones\GenerarRemisionDesdePedidoRequest;
use App\Models\PedidoErp;
use App\Models\RemisionErp;
use App\Services\Remisiones\RemisionErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RemisionErpController extends Controller
{
    public function __construct(
        protected RemisionErpService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);

        $perPage = $perPage > 0 ? min($perPage, 100) : 15;
        $page = $page > 0 ? $page : 1;

        $query = RemisionErp::query()
            ->with([
                'pedido',
                'serieSucursal',
                'sucursalOrigen',
                'sucursalDestino',
            ]);

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        if ($request->filled('pedido_id')) {
            $query->where('pedido_id', $request->input('pedido_id'));
        }

        if ($request->filled('sucursal_destino_id')) {
            $query->where('sucursal_destino_id', $request->input('sucursal_destino_id'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_remision', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_remision', '<=', $request->input('fecha_hasta'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('observaciones', 'like', "%{$search}%")
                    ->orWhere('pedido_id', 'like', "%{$search}%")
                    ->orWhereHas('pedido', function ($pedidoQuery) use ($search) {
                        $pedidoQuery->where('folio', 'like', "%{$search}%")
                            ->orWhere('observaciones', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderByDesc('id');

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'Remisiones ERP obtenidas correctamente.',
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ],
        ]);
    }

    public function generarDesdePedido(GenerarRemisionDesdePedidoRequest $request, PedidoErp $pedidoErp): JsonResponse
    {
        try {
            $remision = $this->service->generarDesdePedido(
                $pedidoErp,
                $request->validated(),
                (string) ($request->user()?->id ?? $request->user()?->name ?? 'system')
            );

            return response()->json([
                'success' => true,
                'message' => 'Remisión generada correctamente.',
                'data' => $remision,
            ], 201);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al generar la remisión.',
            ], 500);
        }
    }

    public function show(RemisionErp $remisionErp): JsonResponse
    {
        $remisionErp->load([
            'detalles.producto',
            'pedido',
            'serieSucursal',
            'sucursalOrigen',
            'sucursalDestino',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Remisión obtenida correctamente.',
            'data' => $remisionErp,
        ]);
    }
}