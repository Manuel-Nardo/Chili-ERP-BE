<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class PosApiService
{
    public function obtenerHistoricoProductos(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.pos.base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('No está configurado services.pos.base_url.');
        }

        $request = $this->buildRequest();

        try {
            $response = $request->post("{$baseUrl}/api/forecast/historico-productos", $payload);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Error al conectar con POS para obtener histórico: ' . $e->getMessage(),
                previous: $e
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'No fue posible obtener histórico desde POS. HTTP ' . $response->status() . ' - ' . $response->body()
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('Respuesta inválida del POS: no se recibió un JSON válido.');
        }

        if (! ($json['success'] ?? false)) {
            throw new RuntimeException($json['message'] ?? 'Respuesta inválida del POS.');
        }

        return is_array($json['data'] ?? null) ? $json['data'] : [];
    }

    protected function buildRequest(): PendingRequest
    {
        $request = Http::acceptJson();

        if (app()->environment('local')) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }
}