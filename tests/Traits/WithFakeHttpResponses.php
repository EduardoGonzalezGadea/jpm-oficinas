<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

/**
 * Trait WithFakeHttpResponses
 * 
 * Proporciona helpers para mockear respuestas HTTP en tests.
 * Útil para tests que dependen de APIs externas (BPS, BCU, etc.)
 */
trait WithFakeHttpResponses
{
    /**
     * Configura una respuesta fake para la API de valores UR del BPS
     */
    protected function fakeValorUrResponse(float $valor = 1500.50, string $fecha = '2026-08-14'): void
    {
        Http::fake([
            '*bps.gub.uy*' => Http::response([
                'valor' => $valor,
                'fecha' => $fecha,
                'moneda' => 'UR',
            ], 200),
        ]);
    }

    /**
     * Configura una respuesta fake para la API de sincronización de hora
     */
    protected function fakeHoraSincronizadaResponse(string $datetime = null): void
    {
        $datetime = $datetime ?? now()->toIso8601String();

        Http::fake([
            '*worldtimeapi.org*' => Http::response([
                'datetime' => $datetime,
                'timezone' => 'America/Montevideo',
            ], 200),
            '*timeapi.io*' => Http::response([
                'dateTime' => $datetime,
                'timeZone' => 'America/Montevideo',
            ], 200),
        ]);
    }

    /**
     * Configura una respuesta fake para la API de valores SOA del BCU
     */
    protected function fakeValoresSoaResponse(array $valores = []): void
    {
        if (empty($valores)) {
            $valores = [
                ['tipo' => 'SOA1', 'valor' => 1000],
                ['tipo' => 'SOA2', 'valor' => 2000],
                ['tipo' => 'SOA3', 'valor' => 3000],
            ];
        }

        Http::fake([
            '*bcu.gub.uy*' => Http::response(['valores' => $valores], 200),
        ]);
    }

    /**
     * Configura que todas las peticiones HTTP fallen (para tests de manejo de errores)
     */
    protected function fakeHttpFailures(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);
    }

    /**
     * Configura que todas las peticiones HTTP tengan timeout
     */
    protected function fakeHttpTimeouts(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
        ]);
    }

    /**
     * Configura respuestas HTTP específicas por URL
     */
    protected function fakeHttpResponses(array $responses): void
    {
        Http::fake($responses);
    }

    /**
     * Verifica que se hizo una petición HTTP a una URL específica
     */
    protected function assertHttpRequestSent(string $url): void
    {
        Http::assertSent(function ($request) use ($url) {
            return str_contains($request->url(), $url);
        });
    }

    /**
     * Verifica que NO se hizo ninguna petición HTTP
     */
    protected function assertNoHttpRequestsSent(): void
    {
        Http::assertNothingSent();
    }

    /**
     * Verifica el número de peticiones HTTP realizadas
     */
    protected function assertHttpRequestCount(int $count): void
    {
        Http::assertSentCount($count);
    }
}
