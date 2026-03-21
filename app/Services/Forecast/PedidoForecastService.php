<?php

namespace App\Services\Forecast;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PedidoForecastService
{
    public function __construct(
        protected ForecastHistoricoService $historicoService,
        protected ForecastMotorService $motorService
    ) {}

    public function generarForecast(
        int $sucursalId,
        int $clasificadorId,
        string $fechaObjetivo,
        int $diasHistorico = 84
    ): Collection {
        $fechaObjetivoCarbon = Carbon::parse($fechaObjetivo);
        $fechaFinHistorico   = $fechaObjetivoCarbon->copy()->subDay()->format('Y-m-d');
        $fechaInicioHistorico = $fechaObjetivoCarbon->copy()->subDay()->subDays($diasHistorico - 1)->format('Y-m-d');

        $productos = $this->historicoService->obtenerProductosContexto($sucursalId, $clasificadorId);

        $historico = $this->historicoService->obtenerHistoricoVentas(
            $sucursalId,
            $clasificadorId,
            $fechaInicioHistorico,
            $fechaFinHistorico
        );

        return $productos->map(function ($producto) use ($historico, $fechaObjetivo) {
            $historicoProducto = $historico
                ->where('producto_id', $producto['producto_id'])
                ->values();

            return $this->motorService->calcularForecastProducto(
                producto: $producto,
                historicoProducto: $historicoProducto,
                fechaObjetivo: $fechaObjetivo
            );
        });
    }
}