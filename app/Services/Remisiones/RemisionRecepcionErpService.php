<?php

namespace App\Services\Remisiones;

use App\Models\RemisionErp;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RemisionRecepcionErpService
{
    /**
     * Listado de remisiones para recepción.
     */
    public function listado(array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $query = RemisionErp::query()
            ->with([
                'pedido',
                'sucursalOrigen',
                'sucursalDestino',
            ])
            ->withCount('detalles')
            ->when(
                !empty($filters['solo_pendientes']),
                function (Builder $q) {
                    $q->whereIn('estatus', [
                        'GENERADA',
                        'ENVIADA',
                        'RECIBIDA_PARCIAL',
                    ]);
                }
            )
            ->when(
                !empty($filters['estatus']),
                fn (Builder $q) => $q->where('estatus', $filters['estatus'])
            )
            ->when(
                !empty($filters['fecha_desde']),
                fn (Builder $q) => $q->whereDate('fecha_remision', '>=', $filters['fecha_desde'])
            )
            ->when(
                !empty($filters['fecha_hasta']),
                fn (Builder $q) => $q->whereDate('fecha_remision', '<=', $filters['fecha_hasta'])
            )
            ->when(
                !empty($filters['sucursal_destino_id']),
                fn (Builder $q) => $q->where('sucursal_destino_id', $filters['sucursal_destino_id'])
            )
            ->when(
                !empty($filters['pedido_erp_id']),
                fn (Builder $q) => $q->where('pedido_erp_id', $filters['pedido_erp_id'])
            )
            ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                $search = trim((string) $filters['search']);

                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('folio', 'like', "%{$search}%")
                        ->orWhereHas('pedido', function (Builder $pedido) use ($search) {
                            $pedido->where('folio', 'like', "%{$search}%");
                        })
                        ->orWhereHas('sucursalOrigen', function (Builder $sucursal) use ($search) {
                            $sucursal->where('nombre', 'like', "%{$search}%");
                        })
                        ->orWhereHas('sucursalDestino', function (Builder $sucursal) use ($search) {
                            $sucursal->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id');

        return $query->paginate($perPage);
    }

    /**
     * Obtener remisión para vista de recepción.
     */
    public function findForRecepcion(int $id): RemisionErp
    {
        return RemisionErp::query()
            ->with([
                'pedido',
                'sucursalOrigen',
                'sucursalDestino',
                'detalles.producto',
            ])
            ->findOrFail($id);
    }

    /**
     * Confirmar recepción de remisión.
     */
    public function recibir(int $id, array $payload, ?int $userId = null): RemisionErp
    {
        return DB::transaction(function () use ($id, $payload, $userId) {
            /** @var RemisionErp $remision */
            $remision = RemisionErp::query()
                ->with('detalles')
                ->lockForUpdate()
                ->findOrFail($id);

            $this->validarRemisionRecibible($remision);

            $detallesPayload = collect($payload['detalles'] ?? [])->keyBy('id');

            if ($detallesPayload->isEmpty()) {
                throw new InvalidArgumentException('Debes enviar al menos un detalle para recibir la remisión.');
            }

            $detalleIdsRemision = $remision->detalles
                ->pluck('id')
                ->map(fn ($detalleId) => (int) $detalleId)
                ->values();

            $detalleIdsPayload = $detallesPayload->keys()
                ->map(fn ($detalleId) => (int) $detalleId)
                ->values();

            $faltantesEnPayload = $detalleIdsRemision->diff($detalleIdsPayload)->values();
            if ($faltantesEnPayload->isNotEmpty()) {
                throw new InvalidArgumentException(
                    'Faltan detalles por enviar en la recepción: ' . $faltantesEnPayload->implode(', ')
                );
            }

            $extrasEnPayload = $detalleIdsPayload->diff($detalleIdsRemision)->values();
            if ($extrasEnPayload->isNotEmpty()) {
                throw new InvalidArgumentException(
                    'Se enviaron detalles que no pertenecen a la remisión: ' . $extrasEnPayload->implode(', ')
                );
            }

            $resumen = $this->procesarDetallesRecepcion($remision, $detallesPayload);

            $remision->fecha_recepcion = !empty($payload['fecha_recepcion'])
                ? Carbon::parse($payload['fecha_recepcion'])->format('Y-m-d')
                : now()->format('Y-m-d');

            $remision->recibido_por = $userId;
            $remision->recibido_at = now();
            $remision->observaciones_recepcion = $payload['observaciones_recepcion'] ?? null;
            $remision->estatus = $this->resolverEstatusRemision($resumen);
            $remision->save();

            return $this->findForRecepcion($remision->id);
        });
    }

    /**
     * Procesa detalle por detalle y devuelve resumen.
     */
    protected function procesarDetallesRecepcion(RemisionErp $remision, Collection $detallesPayload): array
    {
        $totalDetalles = 0;
        $totalRecibidosCompletos = 0;
        $totalRecibidosParciales = 0;
        $totalNoRecibidos = 0;

        foreach ($remision->detalles as $detalle) {
            $incoming = $detallesPayload->get($detalle->id);

            if (!$incoming) {
                throw new InvalidArgumentException("No se encontró información para el detalle {$detalle->id}.");
            }

            $cantidadEnviada = (float) $detalle->cantidad;
            $cantidadRecibida = (float) ($incoming['cantidad_recibida'] ?? 0);

            if ($cantidadRecibida < 0) {
                throw new InvalidArgumentException(
                    "La cantidad recibida del detalle {$detalle->id} no puede ser negativa."
                );
            }

            if ($cantidadRecibida > $cantidadEnviada) {
                throw new InvalidArgumentException(
                    "La cantidad recibida del detalle {$detalle->id} no puede ser mayor a la enviada."
                );
            }

            $diferencia = $cantidadEnviada - $cantidadRecibida;
            $estatusDetalle = $this->resolverEstatusDetalle($cantidadEnviada, $cantidadRecibida);

            $detalle->cantidad_recibida = $cantidadRecibida;
            $detalle->diferencia = $diferencia;
            $detalle->estatus = $estatusDetalle;
            $detalle->observaciones_recepcion = $incoming['observaciones_recepcion'] ?? null;
            $detalle->save();

            $totalDetalles++;

            if ($estatusDetalle === 'RECIBIDO') {
                $totalRecibidosCompletos++;
            } elseif ($estatusDetalle === 'RECIBIDO_PARCIAL') {
                $totalRecibidosParciales++;
            } elseif ($estatusDetalle === 'NO_RECIBIDO') {
                $totalNoRecibidos++;
            }
        }

        return [
            'total_detalles' => $totalDetalles,
            'completos' => $totalRecibidosCompletos,
            'parciales' => $totalRecibidosParciales,
            'no_recibidos' => $totalNoRecibidos,
        ];
    }

    /**
     * Valida si la remisión puede recibirse.
     */
    protected function validarRemisionRecibible(RemisionErp $remision): void
    {
        if ($remision->estatus === 'CANCELADA') {
            throw new InvalidArgumentException('La remisión está cancelada y no puede recibirse.');
        }

        if ($remision->estatus === 'RECIBIDA_COMPLETA') {
            throw new InvalidArgumentException('La remisión ya fue recibida completamente.');
        }

        if ($remision->detalles->isEmpty()) {
            throw new InvalidArgumentException('La remisión no contiene detalles para recibir.');
        }
    }

    /**
     * Resuelve estatus de un detalle.
     */
    protected function resolverEstatusDetalle(float $cantidadEnviada, float $cantidadRecibida): string
    {
        if ($cantidadRecibida <= 0) {
            return 'NO_RECIBIDO';
        }

        if ($cantidadRecibida < $cantidadEnviada) {
            return 'RECIBIDO_PARCIAL';
        }

        return 'RECIBIDO';
    }

    /**
     * Resuelve estatus general de remisión.
     */
    protected function resolverEstatusRemision(array $resumen): string
    {
        $total = (int) ($resumen['total_detalles'] ?? 0);
        $completos = (int) ($resumen['completos'] ?? 0);
        $parciales = (int) ($resumen['parciales'] ?? 0);
        $noRecibidos = (int) ($resumen['no_recibidos'] ?? 0);

        if ($total <= 0) {
            throw new InvalidArgumentException('No fue posible determinar el estatus de la remisión.');
        }

        if ($completos === $total) {
            return 'RECIBIDA_COMPLETA';
        }

        if (($parciales + $noRecibidos + $completos) > 0) {
            return 'RECIBIDA_PARCIAL';
        }

        return 'ENVIADA';
    }
}