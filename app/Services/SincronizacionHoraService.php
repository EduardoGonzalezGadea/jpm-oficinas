<?php

namespace App\Services;

use App\Services\Http\HttpClientService;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SincronizacionHoraService
{
    private const CACHE_KEY = 'sincronizacion_hora_actual';
    private const CACHE_TTL_MINUTES = 10;

    private HttpClientService $httpClient;

    public function __construct(HttpClientService $httpClient = null)
    {
        $this->httpClient = $httpClient ?? app(HttpClientService::class);
    }

    public function obtener(): array
    {
        // Si estamos en desarrollo local (artisan serve), usar hora local directamente
        // para evitar timeout con APIs externas
        if (app()->environment('local') && request()->getHost() === '127.0.0.1') {
            Log::debug("SincronizacionHoraService: Modo desarrollo detectado, usando hora local del servidor");
            return $this->fallbackServidorLocal();
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            // Recalcular datetime usando el offset sincronizado en lugar del reloj local,
            // que puede estar desincronizado respecto a la hora real.
            if (!empty($cached['synced']) && !empty($cached['utc_reference'])) {
                $elapsed = time() - $cached['utc_reference'];
                $remoteTime = Carbon::createFromTimestamp($cached['utc_timestamp'] + $elapsed, 'America/Montevideo');
                $cached['datetime'] = $remoteTime->toIso8601String();
                $cached['drift_seconds'] = 0;
            } else {
                $cached['datetime'] = now('America/Montevideo')->toIso8601String();
                $cached['drift_seconds'] = 0;
            }
            return $cached;
        }

        Log::info("SincronizacionHoraService: Iniciando sincronización de hora desde APIs externas");

        $config = config('external_downloads.sincronizacion_hora', []);
        $urls = $config['urls'] ?? [
            'https://worldtimeapi.org/api/timezone/America/Montevideo',
            'https://timeapi.io/api/Time/current/zone?timeZone=America/Montevideo',
            'http://worldtimeapi.org/api/timezone/America/Montevideo',
        ];

        foreach ($urls as $url) {
            $resultado = $this->intentarApi($url, $config);
            if ($resultado['synced']) {
                Log::info("SincronizacionHoraService: Hora sincronizada exitosamente desde {$resultado['source']}");
                Cache::put(
                    self::CACHE_KEY,
                    $resultado,
                    now()->addMinutes($config['cache_ttl_minutes'] ?? self::CACHE_TTL_MINUTES)
                );
                return $resultado;
            }
        }

        Log::warning("SincronizacionHoraService: Todas las APIs fallaron, usando hora del servidor como fallback");
        $resultado = $this->fallbackServidorLocal();
        Cache::put(
            self::CACHE_KEY,
            $resultado,
            now()->addMinutes($config['cache_ttl_minutes'] ?? self::CACHE_TTL_MINUTES)
        );

        return $resultado;
    }

    protected function intentarApi(string $url, array $config): array
    {
        try {
            $response = $this->httpClient->getWithRetry(
                $url,
                ['timeout' => $config['timeout'] ?? 10],
                $config['max_retries'] ?? 1,
                $config['retry_delay_ms'] ?? 500,
                'sincronizacion_hora'
            );

            if (!$response->successful()) {
                Log::warning("SincronizacionHoraService: Respuesta no exitosa desde {$url} - HTTP {$response->status()}");
                return $this->buildResult('server', false);
            }

            $data = $response->json();

            if (is_array($data)) {
                if ($this->isWorldTimeApiResponse($data)) {
                    Log::debug("SincronizacionHoraService: Respuesta WorldTimeAPI detectada");
                    return $this->processWorldTimeApi($data, $config);
                }

                if ($this->isTimeApiIoResponse($data)) {
                    Log::debug("SincronizacionHoraService: Respuesta TimeAPI.io detectada");
                    return $this->processTimeApiIo($data, $config);
                }
            }

            // Fallback confiable: header HTTP Date (RFC 7231) presente en toda
            // respuesta de servidores de confianza (BPS, BCU, Google, etc.)
            if ($this->hasHttpDateHeader($response)) {
                Log::debug("SincronizacionHoraService: Header HTTP Date detectado desde {$url}");
                return $this->processHttpDateHeader($response, $config);
            }

            Log::warning("SincronizacionHoraService: Formato de respuesta no reconocido desde {$url}");

        } catch (\Exception $e) {
            Log::warning("SincronizacionHoraService: Exception al obtener hora desde {$url}: " . $e->getMessage(), [
                'class' => get_class($e),
                'line' => $e->getLine(),
            ]);
        }

        return $this->buildResult('server', false);
    }

    protected function isWorldTimeApiResponse(array $data): bool
    {
        return isset($data['datetime']) && isset($data['timezone']);
    }

    protected function isTimeApiIoResponse(array $data): bool
    {
        return isset($data['year']) && isset($data['month']) && isset($data['day']) && isset($data['hour']);
    }

    protected function hasHttpDateHeader(Response $response): bool
    {
        return !empty($response->header('Date'));
    }

    protected function processHttpDateHeader(Response $response, array $config): array
    {
        try {
            $dateHeader = $response->header('Date');
            if (empty($dateHeader)) {
                return $this->buildResult('http_date', false);
            }

            // Formato RFC 7231, ej: "Sat, 01 Aug 2026 00:36:21 GMT"
            $remoteTime = Carbon::parse($dateHeader);
            $timezone = $config['validation']['expected_timezone'] ?? 'America/Montevideo';
            $localTime = now($timezone);

            $drift = abs($remoteTime->diffInSeconds($localTime));

            $maxDrift = $config['validation']['max_drift_seconds'] ?? 60;
            if ($drift > $maxDrift) {
                Log::warning("SincronizacionHoraService: Drift demasiado grande ({$drift}s) - rechazando header HTTP Date");
                return $this->buildResult('http_date', false);
            }

            $datetime = $remoteTime->copy()->setTimezone($timezone)->toIso8601String();

            return $this->buildResult('http_date', true, $datetime, $timezone, $drift);

        } catch (\Exception $e) {
            Log::warning("SincronizacionHoraService: Error procesando header HTTP Date: " . $e->getMessage());
            return $this->buildResult('http_date', false);
        }
    }

    protected function processWorldTimeApi(array $data, array $config): array
    {
        try {
            $datetime = $data['datetime'] ?? null;
            $timezone = $data['timezone'] ?? 'America/Montevideo';

            if (!$datetime) {
                return $this->buildResult('worldtimeapi', false);
            }

            $remoteTime = Carbon::parse($datetime);
            $localTime = now('America/Montevideo');
            $drift = abs($remoteTime->diffInSeconds($localTime));

            $maxDrift = $config['validation']['max_drift_seconds'] ?? 60;
            if ($drift > $maxDrift) {
                Log::warning("SincronizacionHoraService: Drift demasiado grande ({$drift}s) - rechazando respuesta de WorldTimeAPI");
                return $this->buildResult('worldtimeapi', false);
            }

            return $this->buildResult('worldtimeapi', true, $datetime, $timezone, $drift);

        } catch (\Exception $e) {
            Log::warning("SincronizacionHoraService: Error procesando respuesta WorldTimeAPI: " . $e->getMessage());
            return $this->buildResult('worldtimeapi', false);
        }
    }

    protected function processTimeApiIo(array $data, array $config): array
    {
        try {
            $datetime = sprintf(
                '%04d-%02d-%02dT%02d:%02d:%02d',
                $data['year'] ?? now()->year,
                $data['month'] ?? now()->month,
                $data['day'] ?? now()->day,
                $data['hour'] ?? now()->hour,
                $data['minute'] ?? now()->minute,
                $data['seconds'] ?? now()->second
            );

            $timezone = $data['timeZone'] ?? 'America/Montevideo';

            // timeapi.io no incluye offset en la respuesta; los valores son hora local
            // del timezone solicitado, por lo que deben interpretarse con ese timezone.
            $remoteTime = Carbon::createFromFormat('Y-m-d\TH:i:s', $datetime, $timezone);
            $localTime = now($timezone);
            $drift = abs($remoteTime->diffInSeconds($localTime));

            $maxDrift = $config['validation']['max_drift_seconds'] ?? 60;
            if ($drift > $maxDrift) {
                Log::warning("SincronizacionHoraService: Drift demasiado grande ({$drift}s) - rechazando respuesta de TimeAPI.io");
                return $this->buildResult('timeapi', false);
            }

            return $this->buildResult('timeapi', true, $datetime, $timezone, $drift);

        } catch (\Exception $e) {
            Log::warning("SincronizacionHoraService: Error procesando respuesta TimeAPI.io: " . $e->getMessage());
            return $this->buildResult('timeapi', false);
        }
    }

    protected function fallbackServidorLocal(): array
    {
        Log::info('Usando hora local del servidor como fallback tras fallar todas las APIs');
        return $this->buildResult(
            'server',
            false,
            now('America/Montevideo')->toIso8601String(),
            'America/Montevideo'
        );
    }

    protected function buildResult(
        string $source,
        bool $synced,
        ?string $datetime = null,
        ?string $timezone = null,
        ?int $drift = null
    ): array {
        $result = [
            'success' => true,
            'datetime' => $datetime ?? now('America/Montevideo')->toIso8601String(),
            'timezone' => $timezone ?? 'America/Montevideo',
            'source' => $source,
            'synced' => $synced,
            'drift_seconds' => $drift,
        ];

        // Guardar referencia UTC para poder recalcular la hora correcta desde caché
        // sin depender del reloj local (que puede estar desincronizado)
        if ($synced && $datetime) {
            $result['utc_timestamp'] = Carbon::parse($datetime)->utcOffset(0)->timestamp;
            $result['utc_reference'] = time();
        }

        return $result;
    }
}
