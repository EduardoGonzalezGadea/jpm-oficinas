<?php

namespace App\Console\Commands;

use App\Services\Http\HttpClientService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ExternalTestConnectivityCommand extends Command
{
    protected $signature = 'external:test-connectivity
                            {--service= : Testear solo un servicio (valor_ur, sincronizacion_hora, valores_soa)}
                            {--json : Salida en formato JSON}
                            {--debug : Mostrar detalles de cada intento}';

    protected $description = 'Testea conectividad a URLs externas (BPS, Hora, BCU) con y sin proxy';

    protected HttpClientService $httpClient;
    protected array $config;
    protected bool $json;
    protected bool $debug;

    public function handle()
    {
        $this->httpClient = app(HttpClientService::class);
        $this->config = config('external_downloads') ?? [];
        $this->json = $this->option('json');
        $this->debug = $this->option('debug');

        if (!$this->json) {
            $this->info('═══════════════════════════════════════════════════════════════');
            $this->info('  Test de Conectividad - Descargas de Datos Externos');
            $this->info('═══════════════════════════════════════════════════════════════');
            $this->newLine();
        }

        $results = [];

        // Detectar proxy
        $proxy = $this->httpClient->detectProxy();
        if (!$this->json) {
            $this->line('🔍 Detección de Proxy:');
            if ($proxy) {
                $this->info("   ✅ Proxy detectado: " . $this->maskProxy($proxy));
            } else {
                $this->line("   ⚠️  No hay proxy configurado (HTTP_PROXY, HTTPS_PROXY)");
            }
            $this->newLine();
        }
        $results['proxy'] = $proxy ? $this->maskProxy($proxy) : null;

        // Testear servicios
        $services = $this->getServicesToTest();

        foreach ($services as $serviceName => $serviceConfig) {
            $result = $this->testService($serviceName, $serviceConfig);
            $results[$serviceName] = $result;
        }

        // Mostrar resumen
        if (!$this->json) {
            $this->showSummary($results);
        } else {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return 0;
    }

    /**
     * Obtiene los servicios a testear
     */
    protected function getServicesToTest(): array
    {
        $all = [
            'valor_ur' => $this->config['valor_ur'] ?? [],
            'sincronizacion_hora' => $this->config['sincronizacion_hora'] ?? [],
            'valores_soa' => $this->config['valores_soa'] ?? [],
        ];

        $service = $this->option('service');
        if ($service) {
            return [$service => $all[$service]] ?? [];
        }

        return $all;
    }

    /**
     * Testa un servicio individual
     */
    protected function testService(string $serviceName, array $serviceConfig): array
    {
        if (empty($serviceConfig)) {
            return ['status' => 'skipped', 'reason' => 'No configurado'];
        }

        $urls = [];
        if ($serviceName === 'sincronizacion_hora') {
            $urls = $serviceConfig['urls'] ?? [];
        } else {
            $urls = [$serviceConfig['url'] ?? $serviceConfig['url_source'] ?? ''];
        }

        if (!$this->json) {
            $this->line("🧪 Testeando: <fg=cyan>{$serviceName}</>");
        }

        $results = [
            'name' => $serviceName,
            'enabled' => $serviceConfig['enabled'] ?? false,
            'urls_tested' => [],
            'overall_status' => 'failed',
        ];

        foreach ($urls as $idx => $url) {
            $urlResult = $this->testUrl(
                $url,
                $serviceName,
                $idx + 1,
                count($urls)
            );
            $results['urls_tested'][] = $urlResult;

            // Si alguna URL tuvo éxito, marcar como exitoso
            if ($urlResult['without_proxy']['status'] === 'success' ||
                $urlResult['with_proxy']['status'] === 'success') {
                $results['overall_status'] = 'success';
            }
        }

        return $results;
    }

    /**
     * Testa una URL individual
     */
    protected function testUrl(
        string $url,
        string $serviceName,
        int $attempt,
        int $total
    ): array {
        $result = [
            'url' => $url,
            'without_proxy' => $this->attemptConnection($url, null),
            'with_proxy' => $this->attemptConnection($url, $this->httpClient->detectProxy()),
        ];

        if (!$this->json) {
            $urlLabel = $total > 1 ? "   URL {$attempt}/{$total}: " : "   URL: ";
            $this->line($urlLabel . "<fg=gray>$url</>");

            $this->showTestResult("   Sin proxy", $result['without_proxy']);
            if ($result['with_proxy']['status'] !== 'no_proxy') {
                $this->showTestResult("   Con proxy", $result['with_proxy']);
            }
        }

        return $result;
    }

    /**
     * Intenta conectarse a una URL
     */
    protected function attemptConnection(?string $url, ?string $proxy): array
    {
        if (!$url) {
            return ['status' => 'invalid', 'reason' => 'URL vacía'];
        }

        if (!$proxy && $this->httpClient->detectProxy()) {
            // Ya lo intentamos sin proxy, no repetir
        }

        $startTime = microtime(true);

        try {
            $options = [
                'timeout' => 15,
                'connect_timeout' => 10,
                'verify' => false,
            ];

            if ($proxy) {
                $options['proxy'] = $proxy;
            }

            $response = Http::withOptions($options)->timeout(15)->get($url);

            $duration = (microtime(true) - $startTime) * 1000; // convertir a ms

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'http_code' => $response->status(),
                    'response_time_ms' => round($duration, 2),
                    'content_length' => strlen($response->body()),
                ];
            } else {
                return [
                    'status' => 'http_error',
                    'http_code' => $response->status(),
                    'response_time_ms' => round($duration, 2),
                ];
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return [
                'status' => 'connection_error',
                'error' => $e->getMessage(),
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return [
                'status' => 'request_error',
                'error' => $e->getMessage(),
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'response_time_ms' => round($duration, 2),
            ];
        }
    }

    /**
     * Muestra el resultado de un test
     */
    protected function showTestResult(string $label, array $result): void
    {
        if ($result['status'] === 'success') {
            $this->info($label . ": <fg=green>✅ OK</> ({$result['response_time_ms']}ms)");
        } elseif ($result['status'] === 'http_error') {
            $this->warn($label . ": ⚠️  HTTP {$result['http_code']} ({$result['response_time_ms']}ms)");
        } elseif ($result['status'] === 'connection_error') {
            $this->error($label . ": ❌ Conexión rechazada ({$result['response_time_ms']}ms)");
        } elseif ($result['status'] === 'no_proxy') {
            $this->line($label . ": (sin proxy configurado)");
        } else {
            $errorMsg = $result['error'] ?? 'Error desconocido';
            $this->error($label . ": ❌ {$errorMsg}");
        }
    }

    /**
     * Muestra un resumen final
     */
    protected function showSummary(array $results): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Resumen');
        $this->info('═══════════════════════════════════════════════════════════════');

        $allOk = true;
        foreach ($results as $serviceName => $result) {
            if ($serviceName === 'proxy') {
                continue;
            }

            if (is_array($result) && $result['overall_status'] === 'success') {
                $this->info("✅ {$serviceName}: Conectividad OK");
            } else {
                $this->error("❌ {$serviceName}: Falló");
                $allOk = false;
            }
        }

        $this->newLine();
        if ($allOk) {
            $this->info('✅ Todas las fuentes externas son accesibles');
        } else {
            $this->error('⚠️  Algunos servicios no son accesibles. Ver detalles arriba.');
            $this->line('   Posibles soluciones:');
            $this->line('   - Verificar conexión a Internet');
            $this->line('   - Si hay proxy corporativo, configurar en .env');
            $this->line('   - Verificar si el firewall bloquea las URLs');
            $this->line('   - Ejecutar: php artisan external:test-connectivity --debug');
        }

        $this->info('═══════════════════════════════════════════════════════════════');
    }

    /**
     * Enmascara un proxy para no mostrar detalles sensibles
     */
    protected function maskProxy(string $proxy): string
    {
        // Si tiene credenciales (usuario:contraseña@host:puerto)
        if (preg_match('/^(https?:\/\/)?([^:@]+):([^@]+)@(.+)$/i', $proxy, $matches)) {
            $protocol = $matches[1] ?? 'http://';
            $hostAndPort = $matches[4];
            return "{$protocol}***:***@{$hostAndPort}";
        }
        return $proxy;
    }
}
