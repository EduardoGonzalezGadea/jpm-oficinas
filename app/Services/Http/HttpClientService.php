<?php

namespace App\Services\Http;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Exception;

class HttpClientService
{
    protected array $config;

    protected string $serviceName = 'external_downloads';

    protected ?array $detectedProxy = null;

    public function __construct()
    {
        $this->config = config('external_downloads') ?? [];
    }

    public function getWithRetry(
        string $url,
        array $options = [],
        int $maxRetries = 2,
        int $retryDelayMs = 500,
        string $serviceName = 'unknown'
    ): Response {
        $this->serviceName = $serviceName;

        if ($this->isCircuitBreakerOpen($serviceName)) {
            $this->log('warning', "Circuit breaker abierto para {$serviceName}", ['url' => $url]);
            throw new Exception("Circuit breaker abierto para {$serviceName}");
        }

        $lastException = null;
        $proxyConfig = $this->detectProxy();

        // Validar proxy ANTES de decidir el orden de conexión
        $proxyValid = false;
        if ($proxyConfig !== null) {
            $proxyValid = $this->validateProxy($proxyConfig);
        }

        $modes = [];
        if ($proxyValid) {
            $modes[] = $proxyConfig; // proxy primero si es válido
        }
        $modes[] = null; // directo siempre como fallback

        $label = $proxyConfig === null ? 'none' : ($proxyValid ? 'validado' : 'no_valido');
        $modeLabel = match (true) {
            $proxyValid => 'proxy + directo',
            default => 'solo directo',
        };

        $this->log('info', "Iniciando request", [
            'url' => $url,
            'proxy' => $label,
            'modes' => $modeLabel,
        ]);

        $totalAttempt = 0;
        foreach ($modes as $currentProxy) {
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $totalAttempt++;
                $label = $currentProxy === null ? 'directo' : 'proxy';

                try {
                    $response = $this->executeRequest($url, $options, $currentProxy);

                    if ($response->successful()) {
                        $this->log('info', "Request exitoso ({$label})", [
                            'status' => $response->status(),
                        ]);
                        $this->recordSuccess($serviceName, $url, $response, $currentProxy);
                        return $response;
                    }

                    $this->log('warning', "Response no exitoso ({$label})", [
                        'status' => $response->status(),
                        'attempt' => $totalAttempt,
                    ]);

                } catch (Exception $e) {
                    $lastException = $e;
                    $this->log('warning', "Fallo en request ({$label})", [
                        'error' => $e->getMessage(),
                        'attempt' => $totalAttempt,
                    ]);
                }

                if ($attempt < $maxRetries) {
                    $delay = $this->exponentialBackoff($attempt, $retryDelayMs);
                    usleep($delay * 1000);
                }
            }
        }

        $this->recordFailure($serviceName, $url, $lastException);
        $this->openCircuitBreaker($serviceName);

        throw $lastException ?? new Exception("Todos los intentos de {$url} fallaron");
    }

    protected function executeRequest(string $url, array $options, ?string $proxyConfig): Response
    {
        $requestOptions = $this->buildRequestOptions($options, $proxyConfig);

        if ($this->config['global']['debug'] ?? false) {
            $this->log('debug', "Ejecutando request", [
                'url' => $url,
                'options' => $requestOptions,
            ]);
        }

        $client = Http::withOptions($requestOptions);

        $headers = $requestOptions['headers'] ?? [];
        foreach ($headers as $key => $value) {
            $client = $client->withHeaders([$key => $value]);
        }

        $response = $client->timeout($requestOptions['timeout'] ?? 8)->get($url);

        return $response;
    }

    protected function buildRequestOptions(array $userOptions, ?string $proxyConfig): array
    {
        $globalTimeout = $this->config['global']['timeout_default'] ?? 8;
        $connectTimeout = $this->config['global']['connect_timeout'] ?? 5;
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

        if ($proxyConfig !== null) {
            $options['proxy'] = $proxyConfig;
        } else {
            $options['proxy'] = false; // explicitly no proxy (evita que Guzzle lea HTTP_PROXY del env)
        }

        if (isset($userOptions['headers'])) {
            $options['headers'] = array_merge($options['headers'], $userOptions['headers']);
        }

        return $options;
    }

    public function detectProxy(): ?string
    {
        // Cachear detección por 1 hora
        $cacheKey = 'proxy_detection_' . md5(json_encode([
            $_ENV['HTTP_PROXY'] ?? '',
            $_ENV['HTTPS_PROXY'] ?? '',
        ]));

        if ($this->config['proxy']['cache_detection'] ?? true) {
            if ($cached = Cache::get($cacheKey)) {
                return $cached === 'none' ? null : $cached;
            }
        }

        $proxyEnvVars = ['HTTPS_PROXY', 'HTTP_PROXY', 'https_proxy', 'http_proxy'];
        $proxy = null;

        foreach ($proxyEnvVars as $var) {
            if ($value = getenv($var)) {
                $proxy = $value;
                break;
            }
            if (isset($_ENV[$var]) && !empty($_ENV[$var])) {
                $proxy = $_ENV[$var];
                break;
            }
            if (isset($_SERVER[$var]) && !empty($_SERVER[$var])) {
                $proxy = $_SERVER[$var];
                break;
            }
        }

        if ($this->config['proxy']['cache_detection'] ?? true) {
            Cache::put($cacheKey, $proxy ?? 'none', 3600);
        }

        return $proxy;
    }

    public function validateProxy(string $proxyUrl): bool
    {
        $validationConfig = $this->config['proxy']['validation'] ?? [];
        $cacheKey = 'proxy_validated_' . md5($proxyUrl);

        if ($validationConfig['enabled'] ?? true) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached === 'valid';
            }
        }

        $checkUrl = $validationConfig['url'] ?? 'https://www.google.com';
        $timeout = $validationConfig['timeout'] ?? 5;
        $connectTimeout = $validationConfig['connect_timeout'] ?? 3;

        try {
            $response = Http::withOptions([
                'proxy' => $proxyUrl,
                'timeout' => $timeout,
                'connect_timeout' => $connectTimeout,
                'verify' => $this->config['global']['verify_ssl'] ?? false,
                'headers' => [
                    'User-Agent' => $this->config['global']['user_agent'] ?? 'Laravel HTTP Client',
                ],
            ])->head($checkUrl);

            $isValid = $response->successful();

            if ($isValid) {
                $this->log('info', 'Proxy validado exitosamente', ['proxy' => $proxyUrl]);
            } else {
                $this->log('warning', 'Proxy respondió con estado no exitoso', [
                    'proxy' => $proxyUrl,
                    'status' => $response->status(),
                ]);
            }
        } catch (Exception $e) {
            $this->log('warning', 'Proxy no válido - conexión fallida', [
                'proxy' => $proxyUrl,
                'error' => $e->getMessage(),
            ]);
            $isValid = false;
        }

        $cacheTtl = $validationConfig['cache_ttl_seconds'] ?? 1800;
        Cache::put($cacheKey, $isValid ? 'valid' : 'invalid', $cacheTtl);

        return $isValid;
    }

    protected function exponentialBackoff(int $attempt, int $baseDelayMs): int
    {
        return (2 ** ($attempt - 1)) * $baseDelayMs;
    }

    protected function recordSuccess(string $serviceName, string $url, Response $response, ?string $proxyConfig): void
    {
        $this->log('info', "Request exitoso", [
            'service' => $serviceName,
            'url' => $url,
            'status' => $response->status(),
            'size' => strlen($response->body()),
        ]);

        $this->resetCircuitBreaker($serviceName);
    }

    protected function recordFailure(string $serviceName, string $url, ?Exception $exception): void
    {
        $this->log('error', "Request fallido después de todos los intentos", [
            'service' => $serviceName,
            'url' => $url,
            'error' => $exception?->getMessage(),
        ]);
    }

    protected function isCircuitBreakerOpen(string $serviceName): bool
    {
        if (!($this->config['global']['circuit_breaker']['enabled'] ?? true)) {
            return false;
        }

        $cacheKey = $this->config['global']['circuit_breaker']['cache_key_prefix'] . $serviceName;
        return Cache::has($cacheKey . '_open');
    }

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

    public function resetCircuitBreaker(string $serviceName): void
    {
        $cacheKey = $this->config['global']['circuit_breaker']['cache_key_prefix'] . $serviceName;
        Cache::forget($cacheKey . '_open');
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $logContext = array_merge(['service' => $this->serviceName], $context);

        Log::channel('single')->{$level}(
            "[External Downloads] {$message}",
            $logContext
        );
    }
}
