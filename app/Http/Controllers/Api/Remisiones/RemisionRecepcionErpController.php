<?php

namespace App\Http\Controllers\Api\Remisiones;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecibirRemisionErpRequest;
use App\Http\Resources\RemisionErpResource;
use App\Services\Remisiones\RemisionRecepcionErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class RemisionRecepcionErpController extends Controller
{
    public function __construct(
        protected RemisionRecepcionErpService $service
    ) {
    }

    /**
     * Listado de remisiones para recepción.
     */
    public function listado(Request $request): JsonResponse
    {
        try {
            $listado = $this->service->listado($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Remisiones de recepción obtenidas correctamente.',
                'data' => RemisionErpResource::collection($listado->items()),
                'meta' => [
                    'current_page' => $listado->currentPage(),
                    'last_page' => $listado->lastPage(),
                    'per_page' => $listado->perPage(),
                    'total' => $listado->total(),
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el listado de remisiones para recepción.',
            ], 500);
        }
    }

    /**
     * Detalle de una remisión para recepción.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $id = (int) $request->input('id');

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes enviar el id de la remisión.',
                ], 422);
            }

            $remision = $this->service->findForRecepcion($id);

            return response()->json([
                'success' => true,
                'message' => 'Detalle de remisión obtenido correctamente.',
                'data' => new RemisionErpResource($remision),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No fue posible obtener el detalle de la remisión.',
            ], 500);
        }
    }

    /**
     * Confirmar recepción de remisión.
     */
    public function recibir(RecibirRemisionErpRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $id = (int) ($validated['id'] ?? 0);
            $userId = $request->user()?->id ? (int) $request->user()->id : null;

            if ($id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes enviar el id de la remisión.',
                ], 422);
            }

            $remision = $this->service->recibir(
                $id,
                $validated,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'La remisión fue recibida correctamente.',
                'data' => new RemisionErpResource($remision),
            ]);
        } catch (InvalidArgumentException | RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => app()->environment(['local', 'development'])
                    ? $e->getMessage()
                    : 'Ocurrió un error al recibir la remisión.',
                'line' => app()->environment(['local', 'development']) ? $e->getLine() : null,
                'file' => app()->environment(['local', 'development']) ? $e->getFile() : null,
            ], 500);
        }
    }
}