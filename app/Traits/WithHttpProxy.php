<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trait WithHttpProxy
 *
 * Proporciona métodos reutilizables para realizar peticiones HTTP
 * con soporte de proxy, reintentos y backoff exponencial.
 *
 * La lógica sigue el mismo patrón probado y funcional de ValorUrService.
 */
trait WithHttpProxy
{
    /**
     * Realiza una petición HTTP GET con soporte de proxy, reintentos
     * y backoff exponencial.
     *
     * Por cada intento prueba primero SIN proxy, luego CON proxy.
     *
     * @param  string  $url
     * @param  int     $timeout          Timeout general en segundos
     * @param  int     $maxRetries       Máximo de reintentos (cada reintento = 2 intentos: sin proxy + con proxy)
     * @param  int     $connectTimeout   Timeout de conexión en segundos
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \RuntimeException
     */
    protected function httpGetWithRetry(
        string $url,
        int $timeout = 45,
        int $maxRetries = 3,
        int $connectTimeout = 15
    ) {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'es-UY,es;q=0.9',
        ];

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Cada intento: primero SIN proxy, luego CON proxy
            foreach ([false, true] as $useProxy) {
                try {
                    $options = [
                        'timeout' => $timeout,
                        'connect_timeout' => $connectTimeout,
                        'verify' => false,
                        'headers' => $headers,
                    ];

                    if ($useProxy) {
                        $options['proxy'] = $this->buildProxyOptions();
                        if ($options['proxy'] === null) {
                            continue; // No hay proxy configurado, saltar
                        }
                    } else {
                        $options['proxy'] = false;
                    }

                    Log::info(sprintf(
                        'HTTP GET intento %d/%d (%s proxy): %s',
                        $attempt,
                        $maxRetries,
                        $useProxy ? 'con' : 'sin',
                        $url
                    ));

                    $response = Http::withOptions($options)->get($url);

                    if ($response->successful()) {
                        return $response;
                    }

                    throw new \RuntimeException('Respuesta HTTP ' . $response->status());
                } catch (\Throwable $e) {
                    $lastException = $e;
                    Log::warning(sprintf(
                        'HTTP GET falló (%s proxy, intento %d/%d): %s - %s',
                        $useProxy ? 'con' : 'sin',
                        $attempt,
                        $maxRetries,
                        $url,
                        $e->getMessage()
                    ));
                }
            }

            // Backoff exponencial entre reintentos
            if ($attempt < $maxRetries) {
                $delay = (int) pow(2, $attempt) * 500000; // 1s, 2s, 4s...
                Log::info("Esperando " . ($delay / 1000000) . "s antes del siguiente intento a {$url}");
                usleep($delay);
            }
        }

        throw new \RuntimeException(
            'Fallaron todos los intentos para ' . $url .
            '. Último error: ' . ($lastException?->getMessage() ?? 'desconocido')
        );
    }

    /**
     * Construye las opciones de proxy en el formato que Guzzle/Laravel HTTP
     * espera (array asociativo), verificando variables de entorno estándar.
     *
     * Sigue el mismo patrón probado de ValorUrService.
     *
     * @return array<string, mixed>|string|null
     */
    private function buildProxyOptions()
    {
        $httpProxy = $this->getEnvValue('HTTP_PROXY') ?: $this->getEnvValue('http_proxy');
        $httpsProxy = $this->getEnvValue('HTTPS_PROXY') ?: $this->getEnvValue('https_proxy') ?: $httpProxy;

        if (!$httpProxy && !$httpsProxy) {
            return null;
        }

        $proxy = [
            'http' => $httpProxy ?: $httpsProxy,
            'https' => $httpsProxy ?: $httpProxy,
        ];

        $noProxy = $this->getEnvValue('NO_PROXY') ?: $this->getEnvValue('no_proxy');
        if ($noProxy) {
            $proxy['no'] = array_map('trim', explode(',', $noProxy));
        }

        return $proxy;
    }

    /**
     * Obtiene un valor de entorno con soporte para mayúsculas y minúsculas.
     *
     * @param  string  $key
     * @return string|null
     */
    private function getEnvValue(string $key): ?string
    {
        $value = env($key);
        return is_string($value) && $value !== '' ? $value : null;
    }
}