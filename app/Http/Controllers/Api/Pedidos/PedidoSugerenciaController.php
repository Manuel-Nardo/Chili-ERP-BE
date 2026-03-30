<?php

namespace App\Http\Controllers\Api\Pedidos;

use App\Http\Controllers\Controller;
use App\Http\Requests\PedidoSugerencia\GenerarPedidoSugerenciaRequest;
use App\Http\Requests\PedidoSugerencia\StorePedidoSugerenciaRequest;
use App\Http\Requests\PedidoSugerencia\UpdatePedidoSugerenciaRequest;
use App\Http\Resources\PedidoSugerencia\PedidoSugerenciaResource;
use App\Models\PedidoSugerencia;
use App\Services\PedidoSugerenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PedidoSugerenciaController extends Controller
{
    public function __construct(
        protected PedidoSugerenciaService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PedidoSugerencia::query()
            ->with([
                'cliente',
                'tipoPedido',
                'creador',
                'editor',
                'detalles.producto',
            ]);

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', (int) $request->cliente_id);
        }

        if ($request->filled('tipo_pedido_id')) {
            $query->where('tipo_pedido_id', (int) $request->tipo_pedido_id);
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_objetivo', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_objetivo', '<=', $request->fecha_hasta);
        }

        $items = $query
            ->orderByDesc('id')
            ->paginate((int) ($request->get('per_page', 10)));

        return response()->json([
            'success' => true,
            'message' => 'Sugerencias de pedido obtenidas correctamente.',
            'data' => PedidoSugerenciaResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(StorePedidoSugerenciaRequest $request): JsonResponse
    {
        try {
            $sugerencia = $this->service->create(
                $request->validated(),
                $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Sugerencia de pedido creada correctamente.',
                'data' => new PedidoSugerenciaResource($sugerencia),
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
                'message' => 'Ocurrió un error al crear la sugerencia de pedido.',
            ], 500);
        }
    }

    public function show(PedidoSugerencia $pedidoSugerencia): JsonResponse
    {
        $pedidoSugerencia->load([
            'cliente',
            'tipoPedido',
            'creador',
            'editor',
            'detalles.producto',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sugerencia de pedido obtenida correctamente.',
            'data' => new PedidoSugerenciaResource($pedidoSugerencia),
        ]);
    }

    public function update(UpdatePedidoSugerenciaRequest $request, PedidoSugerencia $pedidoSugerencia): JsonResponse
    {
        try {
            $sugerencia = $this->service->update(
                $pedidoSugerencia,
                $request->validated(),
                $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Sugerencia de pedido actualizada correctamente.',
                'data' => new PedidoSugerenciaResource($sugerencia),
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar la sugerencia de pedido.',
            ], 500);
        }
    }

    public function confirmar(Request $request, PedidoSugerencia $pedidoSugerencia): JsonResponse
    {
        try {
            $sugerencia = $this->service->confirm(
                $pedidoSugerencia,
                $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Sugerencia de pedido confirmada correctamente.',
                'data' => new PedidoSugerenciaResource($sugerencia),
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al confirmar la sugerencia de pedido.',
            ], 500);
        }
    }

    public function cancelar(Request $request, PedidoSugerencia $pedidoSugerencia): JsonResponse
    {
        try {
            $sugerencia = $this->service->cancel(
                $pedidoSugerencia,
                $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Sugerencia de pedido cancelada correctamente.',
                'data' => new PedidoSugerenciaResource($sugerencia),
            ]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al cancelar la sugerencia de pedido.',
            ], 500);
        }
    }

    public function generar(GenerarPedidoSugerenciaRequest $request): JsonResponse
    {
        try {
            $sugerencia = $this->service->generarForecast(
                $request->validated(),
                $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Sugerencia generada correctamente.',
                'data' => new PedidoSugerenciaResource($sugerencia),
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
                'message' => 'Ocurrió un error al generar la sugerencia.',
            ], 500);
        }
    }

    public function generarPedido(int $id): JsonResponse
    {
        try {
            $pedido = $this->service->generarPedidoDesdeSugerencia(
                $id,
                request()->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido generado correctamente.',
                'data' => [
                    'id' => $pedido->id,
                    'serie_id' => $pedido->serie_id,
                    'folio' => $pedido->folio,
                    'estatus' => $pedido->estatus,
                    'subtotal' => $pedido->subtotal,
                    'impuestos' => $pedido->impuestos,
                    'total' => $pedido->total,
                    'pedido_sugerencia_id' => $pedido->pedido_sugerencia_id,
                ],
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
                'message' => 'Ocurrió un error al generar el pedido.',
            ], 500);
        }
    }
}