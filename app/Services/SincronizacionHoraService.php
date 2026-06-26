<?php

namespace App\Services;

use App\Services\Http\HttpClientService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para sincronizar fecha y hora desde APIs públicas.
 *
 * Intenta múltiples APIs en orden de preferencia y cachea el resultado.
 * Si todas fallan, retorna la hora del servidor local.
 */
class SincronizacionHoraService
{
    private const CACHE_KEY = 'sincronizacion_hora_actual';
    private const CACHE_TTL_MINUTES = 5;

    private HttpClientService $httpClient;

    public function __construct(HttpClientService $httpClient = null)
    {
        $this->httpClient = $httpClient ?? app(HttpClientService::class);
    }

    /**
     * Obtiene la hora sincronizada de Uruguay
     *
     * @return array{
     *     success: bool,
     *     datetime: string,
     *     timezone: string,
     *     source: string,
     *     synced: bool,
     *     drift_seconds: int|null
     * }
     */
    public function obtener(): array
    {
        // Verificar caché
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            Log::debug("SincronizacionHoraService: Retornando resultado en caché");
            // Siempre retornar la hora actual, no el datetime stale del caché
            $cached['datetime'] = now('America/Montevideo')->toIso8601String();
            $cached['drift_seconds'] = 0;
            return $cached;
        }

        Log::info("SincronizacionHoraService: Iniciando sincronización de hora desde APIs externas");

        $config = config('external_downloads.sincronizacion_hora', []);
        $urls = $config['urls'] ?? [
            'https://worldtimeapi.org/api/timezone/America/Montevideo',
            'https://timeapi.io/api/Time/current/zone?timeZone=America/Montevideo',
            'http://worldtimeapi.org/api/timezone/America/Montevideo',
        ];

        // Intentar cada API
        $total = count($urls);
        foreach ($urls as $idx => $url) {
            $intento = $idx + 1;
            Log::debug("SincronizacionHoraService: URL intento {$intento}/{$total}: {$url}");
            $resultado = $this->intentarApi($url, $idx, $config);
            if ($resultado['synced']) {
                Log::info("SincronizacionHoraService: ✅ Hora sincronizada exitosamente desde {$resultado['source']}");
                // Cachear resultado exitoso
                Cache::put(
                    self::CACHE_KEY,
                    $resultado,
                    now()->addMinutes($config['cache_ttl_minutes'] ?? self::CACHE_TTL_MINUTES)
                );
                return $resultado;
            }
        }

        // Fallback: hora del servidor
        Log::warning("SincronizacionHoraService: ⚠️  Todas las APIs fallaron, usando hora del servidor como fallback");
        $resultado = $this->fallbackServidorLocal();
        Cache::put(
            self::CACHE_KEY,
            $resultado,
            now()->addMinutes($config['cache_ttl_minutes'] ?? self::CACHE_TTL_MINUTES)
        );

        return $resultado;
    }

    /**
     * Intenta obtener la hora desde una API
     */
    protected function intentarApi(string $url, int $idx, array $config): array
    {
        try {
            // Resetear circuit breaker para que cada URL se intente independientemente
            $this->httpClient->resetCircuitBreaker('sincronizacion_hora');
            Log::debug("SincronizacionHoraService: Intentando obtener hora desde {$url}");

            $response = $this->httpClient->getWithRetry(
                $url,
                ['timeout' => $config['timeout'] ?? 15],
                2, // Aumentado a 2 reintentos (es 3 intentos totales: 1 sin retry + 2 reintentos)
                $config['retry_delay_ms'] ?? 500,
                'sincronizacion_hora'
            );

            if (!$response->successful()) {
                Log::warning("SincronizacionHoraService: Respuesta no exitosa desde {$url} - HTTP {$response->status()}");
                return $this->buildResult('server', false);
            }

            $data = $response->json();
            if (!$data) {
                Log::warning("SincronizacionHoraService: No se pudo parsear JSON desde {$url}");
                return $this->buildResult('server', false);
            }

            // Detectar tipo de respuesta y procesar
            if ($this->isWorldTimeApiResponse($data)) {
                Log::debug("SincronizacionHoraService: Respuesta WorldTimeAPI detectada");
                return $this->processWorldTimeApi($data, $config);
            } elseif ($this->isTimeApiIoResponse($data)) {
                Log::debug("SincronizacionHoraService: Respuesta TimeAPI.io detectada");
                return $this->processTimeApiIo($data, $config);
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

    /**
     * Identifica si es respuesta de WorldTimeAPI
     */
    protected function isWorldTimeApiResponse(array $data): bool
    {
        return isset($data['datetime']) && isset($data['timezone']);
    }

    /**
     * Identifica si es respuesta de TimeAPI.io
     */
    protected function isTimeApiIoResponse(array $data): bool
    {
        return isset($data['year']) && isset($data['month']) && isset($data['day']) && isset($data['hour']);
    }

    /**
     * Procesa respuesta de WorldTimeAPI
     */
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
                Log::warning("SincronizacionHoraService: Drift detectado ({$drift}s) - usando hora remota de todas formas");
            }

            return $this->buildResult('worldtimeapi', true, $datetime, $timezone, $drift);

        } catch (\Exception $e) {
            Log::warning("SincronizacionHoraService: Error procesando respuesta WorldTimeAPI: " . $e->getMessage());
            return $this->buildResult('worldtimeapi', false);
        }
    }

    /**
     * Procesa respuesta de TimeAPI.io
     */
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

            $remoteTime = Carbon::parse($datetime);
            $localTime = now('America/Montevideo');
            $drift = abs($remoteTime->diffInSeconds($localTime));

            $maxDrift = $config['validation']['max_drift_seconds'] ?? 60;
            if ($drift > $maxDrift) {
                Log::warning("SincronizacionHoraService: Drift detectado ({$drift}s) - usando hora remota de todas formas");
            }

            return $this->buildResult('timeapi', true, $datetime, $timezone, $drift);

        } catch (\Exception $e) {
            Log::warning("SincronizacionHoraService: Error procesando respuesta TimeAPI.io: " . $e->getMessage());
            return $this->buildResult('timeapi', false);
        }
    }

    /**
     * Fallback: usar hora del servidor local
     */
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

    /**
     * Construye el resultado de sincronización
     */
    protected function buildResult(
        string $source,
        bool $synced,
        ?string $datetime = null,
        ?string $timezone = null,
        ?int $drift = null
    ): array {
        return [
            'success' => true,
            'datetime' => $datetime ?? now('America/Montevideo')->toIso8601String(),
            'timezone' => $timezone ?? 'America/Montevideo',
            'source' => $source,
            'synced' => $synced,
            'drift_seconds' => $drift,
        ];
    }
}
