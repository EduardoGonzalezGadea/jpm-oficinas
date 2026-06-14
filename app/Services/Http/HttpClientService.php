<?php

namespace App\Services\Http;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Exception;

/**
 * Servicio centralizado para realizar requests HTTP a URLs externas.
 *
 * Características:
 * - Auto-detección y aplicación de proxy
 * - Reintentos con exponential backoff
 * - Logging completo
 * - Circuit breaker para fallos persistentes
 * - Validación de respuestas
 */
class HttpClientService
{
    /**
     * @var array Configuración de external_downloads
     */
    protected array $config;

    /**
     * @var string Nombre del servicio (para logging)
     */
    protected string $serviceName = 'external_downloads';

    /**
     * Cache de configuración de proxy detectada
     */
    protected ?array $detectedProxy = null;

    public function __construct()
    {
        $this->config = config('external_downloads') ?? [];
    }

    /**
     * Realiza un GET request con reintentos automáticos
     *
     * @param string $url URL a consultar
     * @param array $options Opciones adicionales (timeout, headers, etc.)
     * @param int $maxRetries Máximo número de reintentos
     * @param int $retryDelayMs Delay entre reintentos (ms)
     * @param string $serviceName Nombre del servicio (para logging)
     *
     * @return Response Respuesta HTTP
     * @throws Exception Si todos los intentos fallan
     */
    public function getWithRetry(
        string $url,
        array $options = [],
        int $maxRetries = 3,
        int $retryDelayMs = 1000,
        string $serviceName = 'unknown'
    ): Response {
        $this->serviceName = $serviceName;

        // Verificar circuit breaker
        if ($this->isCircuitBreakerOpen($serviceName)) {
            $this->log('error', "Circuit breaker abierto para {$serviceName}", ['url' => $url]);
            throw new Exception("Circuit breaker abierto para {$serviceName}");
        }

        $attempt = 0;
        $lastException = null;
        $proxyAttempts = $this->getProxyAttempts();

        $this->log('info', "Iniciando request", [
            'url' => $url,
            'max_retries' => $maxRetries,
            'proxy_attempts' => count($proxyAttempts),
        ]);

        // Reintentos externos (estrategia: intenta N veces, cada vez intentando sin proxy y luego con proxy)
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Dentro de cada reintento, probamos sin proxy y luego con proxy
            foreach ($proxyAttempts as $proxyIdx => $proxyConfig) {
                try {
                    $response = $this->executeRequest(
                        $url,
                        array_merge($options, ['proxy' => $proxyConfig]),
                        $attempt,
                        $proxyIdx
                    );

                    // Si es exitoso, registrar y retornar
                    if ($response->successful()) {
                        $this->recordSuccess($serviceName, $url, $response, $proxyIdx);
                        return $response;
                    }

                    // Status no 2xx, pero no es error de conexión
                    $this->log('warning', "Response no exitoso", [
                        'status' => $response->status(),
                        'attempt' => $attempt,
                        'proxy' => $proxyIdx === 0 ? 'none' : 'configured',
                    ]);

                } catch (Exception $e) {
                    $lastException = $e;
                    $this->log('warning', "Fallo en request", [
                        'error' => $e->getMessage(),
                        'attempt' => $attempt,
                        'proxy' => $proxyIdx === 0 ? 'none' : 'configured',
                    ]);
                }
            }

            // Si no es el último reintento, esperar antes de reintentar
            if ($attempt < $maxRetries) {
                $delay = $this->exponentialBackoff($attempt, $retryDelayMs);
                $this->log('info', "Esperando antes de reintentar", ['delay_ms' => $delay]);
                usleep($delay * 1000); // convertir ms a microsegundos
            }
        }

        // Todos los intentos fallaron
        $this->recordFailure($serviceName, $url, $lastException);
        $this->openCircuitBreaker($serviceName);

        throw $lastException ?? new Exception("Todos los intentos de {$url} fallaron");
    }

    /**
     * Ejecuta un request HTTP individual
     *
     * @param string $url
     * @param array $options
     * @param int $attempt
     * @param int $proxyIdx
     *
     * @return Response
     */
    protected function executeRequest(
        string $url,
        array $options,
        int $attempt,
        int $proxyIdx
    ): Response {
        $requestOptions = $this->buildRequestOptions($options);

        if ($this->config['global']['debug'] ?? false) {
            $this->log('debug', "Ejecutando request", [
                'url' => $url,
                'options' => array_merge($requestOptions, [
                    'proxy' => $proxyIdx === 0 ? 'none' : 'configured (masked)',
                ]),
            ]);
        }

        $client = Http::withOptions($requestOptions);

        // Agregar headers
        $headers = $requestOptions['headers'] ?? [];
        foreach ($headers as $key => $value) {
            $client = $client->withHeaders([$key => $value]);
        }

        $response = $client->timeout(
            $requestOptions['timeout'] ?? 15
        )->get($url);

        return $response;
    }

    /**
     * Construye las opciones de request (timeout, proxy, headers, etc.)
     *
     * @param array $userOptions Opciones del usuario
     * @return array Opciones finales
     */
    protected function buildRequestOptions(array $userOptions): array
    {
        $globalTimeout = $this->config['global']['timeout_default'] ?? 15;
        $connectTimeout = $this->config['global']['connect_timeout'] ?? 10;
        $verifySsl = $this->config['global']['verify_ssl'] ?? false;

        $options = [
            'timeout' => $userOptions['timeout'] ?? $globalTimeout,
            'connect_timeout' => $connectTimeout,
            'verify' => $verifySsl,
            'headers' => [
                'User-Agent' => $this->config['global']['user_agent'] ?? 'Laravel HTTP Client',
                'Accept' => 'application/json, text/html, */*',
            ],
        ];

        // Agregar proxy si está configurado
        if (isset($userOptions['proxy']) && $userOptions['proxy']) {
            $options['proxy'] = $userOptions['proxy'];
        }

        // Mergear opciones del usuario
        if (isset($userOptions['headers'])) {
            $options['headers'] = array_merge($options['headers'], $userOptions['headers']);
        }

        return $options;
    }

    /**
     * Obtiene la lista de intentos de proxy (sin proxy primero, luego con proxy si está configurado)
     *
     * @return array Array con 1 o 2 elementos (sin proxy, y opcionalmente con proxy)
     */
    protected function getProxyAttempts(): array
    {
        $attempts = [
            null, // Intento 1: sin proxy
        ];

        $proxy = $this->detectProxy();
        if ($proxy) {
            $attempts[] = $proxy; // Intento 2: con proxy
        }

        return $attempts;
    }

    /**
     * Detecta la configuración de proxy desde variables de entorno
     *
     * @return string|null URL del proxy (ej: "http://proxy.empresa.com:8080")
     */
    public function detectProxy(): ?string
    {
        // Cachear detección
        $cacheKey = 'proxy_detection_' . md5(json_encode([
            $_ENV['HTTP_PROXY'] ?? '',
            $_ENV['HTTPS_PROXY'] ?? '',
        ]));

        if ($this->config['proxy']['cache_detection'] ?? true) {
            if ($cached = Cache::get($cacheKey)) {
                return $cached === 'none' ? null : $cached;
            }
        }

        // Variables de entorno a verificar (en orden)
        $proxyEnvVars = ['HTTPS_PROXY', 'HTTP_PROXY', 'https_proxy', 'http_proxy'];
        $proxy = null;

        foreach ($proxyEnvVars as $var) {
            if ($value = getenv($var)) {
                $proxy = $value;
                break;
            }
        }

        // Cachear por 1 hora
        if ($this->config['proxy']['cache_detection'] ?? true) {
            Cache::put($cacheKey, $proxy ?? 'none', 3600);
        }

        return $proxy;
    }

    /**
     * Calcula delay exponencial para reintentos
     *
     * @param int $attempt Número de intento (1, 2, 3...)
     * @param int $baseDelayMs Delay base en ms
     *
     * @return int Delay en ms
     */
    protected function exponentialBackoff(int $attempt, int $baseDelayMs): int
    {
        // 2^(attempt-1) * baseDelay
        // Intento 1: 1 * base
        // Intento 2: 2 * base
        // Intento 3: 4 * base
        return (2 ** ($attempt - 1)) * $baseDelayMs;
    }

    /**
     * Registra un request exitoso
     *
     * @param string $serviceName
     * @param string $url
     * @param Response $response
     * @param int $proxyIdx
     */
    protected function recordSuccess(
        string $serviceName,
        string $url,
        Response $response,
        int $proxyIdx
    ): void {
        $this->log('info', "Request exitoso", [
            'service' => $serviceName,
            'url' => $url,
            'status' => $response->status(),
            'proxy' => $proxyIdx === 0 ? 'none' : 'configured',
            'size' => strlen($response->body()),
        ]);

        // Resetear circuit breaker en caso de éxito
        $this->resetCircuitBreaker($serviceName);

        // Registrar en BD si está habilitado
        if ($this->config['logging']['enabled'] ?? true) {
            $this->logToDatabase($serviceName, 'success', $url, [
                'status' => $response->status(),
                'proxy' => $proxyIdx === 0 ? 'none' : 'configured',
            ]);
        }
    }

    /**
     * Registra un request fallido
     *
     * @param string $serviceName
     * @param string $url
     * @param ?Exception $exception
     */
    protected function recordFailure(
        string $serviceName,
        string $url,
        ?Exception $exception
    ): void {
        $this->log('error', "Request fallido después de todos los intentos", [
            'service' => $serviceName,
            'url' => $url,
            'error' => $exception?->getMessage(),
        ]);

        // Registrar en BD si está habilitado
        if ($this->config['logging']['enabled'] ?? true) {
            $this->logToDatabase($serviceName, 'failure', $url, [
                'error' => $exception?->getMessage(),
            ]);
        }
    }

    /**
     * Verifica si el circuit breaker está abierto para este servicio
     *
     * @param string $serviceName
     * @return bool
     */
    protected function isCircuitBreakerOpen(string $serviceName): bool
    {
        if (!($this->config['global']['circuit_breaker']['enabled'] ?? true)) {
            return false;
        }

        $cacheKey = $this->config['global']['circuit_breaker']['cache_key_prefix'] . $serviceName;
        return Cache::has($cacheKey . '_open');
    }

    /**
     * Abre el circuit breaker para este servicio
     *
     * @param string $serviceName
     */
    protected function openCircuitBreaker(string $serviceName): void
    {
        if (!($this->config['global']['circuit_breaker']['enabled'] ?? true)) {
            return;
        }

        $cacheKey = $this->config['global']['circuit_breaker']['cache_key_prefix'] . $serviceName;
        $timeout = $this->config['global']['circuit_breaker']['recovery_timeout'] ?? 300;

        Cache::put($cacheKey . '_open', true, $timeout);

        $this->log('warning', "Circuit breaker abierto", [
            'service' => $serviceName,
            'recovery_seconds' => $timeout,
        ]);
    }

    /**
     * Resetea el circuit breaker para este servicio
     *
     * @param string $serviceName
     */
    protected function resetCircuitBreaker(string $serviceName): void
    {
        $cacheKey = $this->config['global']['circuit_breaker']['cache_key_prefix'] . $serviceName;
        Cache::forget($cacheKey . '_open');
    }

    /**
     * Registra evento en la tabla de logs
     *
     * @param string $serviceName
     * @param string $status
     * @param string $url
     * @param array $details
     */
    protected function logToDatabase(
        string $serviceName,
        string $status,
        string $url,
        array $details
    ): void {
        try {
            // Será implementado cuando creemos el modelo ExternalDownloadLog
            // Por ahora, solo logueamos
        } catch (Exception $e) {
            Log::warning("Error al registrar download log en BD", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log helper
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logContext = array_merge(['service' => $this->serviceName], $context);

        Log::channel('single')->{$level}(
            "[External Downloads] {$message}",
            $logContext
        );

        if ($this->config['global']['debug'] ?? false) {
            logger()->debug("[External Downloads] {$message}", $logContext);
        }
    }
}
