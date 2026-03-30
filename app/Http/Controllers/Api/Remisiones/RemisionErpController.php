<?php

namespace App\Http\Controllers\Api\Remisiones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Remisiones\GenerarRemisionDesdePedidoRequest;
use App\Models\PedidoErp;
use App\Models\RemisionErp;
use App\Services\Remisiones\RemisionErpService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RemisionErpController extends Controller
{
    public function __construct(
        protected RemisionErpService $service
    ) {}

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