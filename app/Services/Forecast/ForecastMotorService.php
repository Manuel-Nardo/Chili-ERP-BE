<?php

namespace App\Services\Forecast;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ForecastMotorService
{
    public function calcularForecastProducto(
        array $producto,
        Collection $historicoProducto,
        string $fechaObjetivo
    ): array {
        $fechaObjetivoCarbon = Carbon::parse($fechaObjetivo);
        $diaSemanaObjetivo   = (int) $fechaObjetivoCarbon->dayOfWeekIso; // 1=lun ... 7=dom

        $historico = $historicoProducto
            ->map(function ($row) {
                $fecha = Carbon::parse($row['fecha']);

                return [
                    'fecha'          => $row['fecha'],
                    'ventas_dia'     => (int) $row['ventas_dia'],
                    'dia_semana_iso' => (int) $fecha->dayOfWeekIso,
                ];
            })
            ->sortBy('fecha')
            ->values();

        $mismoDiaSemana = $historico
            ->where('dia_semana_iso', $diaSemanaObjetivo)
            ->sortByDesc('fecha')
            ->take(4)
            ->pluck('ventas_dia')
            ->values();

        $ultimos7 = $historico
            ->sortByDesc('fecha')
            ->take(7)
            ->pluck('ventas_dia')
            ->values();

        $promedioDow = $mismoDiaSemana->count() > 0
            ? round($mismoDiaSemana->avg(), 2)
            : 0;

        $promedioReciente = $ultimos7->count() > 0
            ? round($ultimos7->avg(), 2)
            : 0;

        $promedioGeneral = $historico->count() > 0
            ? round($historico->avg('ventas_dia'), 2)
            : 0;

        $forecastBruto =
            ($promedioDow * 0.50) +
            ($promedioReciente * 0.30) +
            ($promedioGeneral * 0.20);

        $forecastFinal = max(0, (int) round($forecastBruto));

        return [
            'producto_id'      => (int) $producto['producto_id'],
            'producto_nombre'  => $producto['producto_nombre'],
            'erp_producto_id'  => $producto['erp_producto_id'] ?? null,
            'erp_clave'        => $producto['erp_clave'] ?? null,
            'fecha_objetivo'   => $fechaObjetivo,
            'metricas' => [
                'historico_count'    => $historico->count(),
                'dow_count'          => $mismoDiaSemana->count(),
                'ultimos7_count'     => $ultimos7->count(),
                'promedio_dow'       => $promedioDow,
                'promedio_reciente'  => $promedioReciente,
                'promedio_general'   => $promedioGeneral,
                'forecast_bruto'     => round($forecastBruto, 2),
            ],
            'sugerido_final' => $forecastFinal,
        ];
    }
}