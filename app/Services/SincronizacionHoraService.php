<?php

namespace App\Services;

use App\Services\Http\HttpClientService;
use Carbon\Carbon;
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
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
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
            if (!$data) {
                Log::warning("SincronizacionHoraService: No se pudo parsear JSON desde {$url}");
                return $this->buildResult('server', false);
            }

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

    protected function isWorldTimeApiResponse(array $data): bool
    {
        return isset($data['datetime']) && isset($data['timezone']);
    }

    protected function isTimeApiIoResponse(array $data): bool
    {
        return isset($data['year']) && isset($data['month']) && isset($data['day']) && isset($data['hour']);
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
                Log::warning("SincronizacionHoraService: Drift detectado ({$drift}s) - usando hora remota de todas formas");
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
