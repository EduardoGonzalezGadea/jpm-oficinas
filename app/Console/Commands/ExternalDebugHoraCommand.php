<?php

namespace App\Console\Commands;

use App\Services\SincronizacionHoraService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExternalDebugHoraCommand extends Command
{
    protected $signature = 'external:debug-hora
                            {--clear-cache : Limpiar caché de sincronización antes de testear}
                            {--debug : Mostrar logs detallados}';

    protected $description = 'Debuggea la sincronización de hora en detalle';

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Debug: Sincronización de Hora');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Limpiar caché si se solicita
        if ($this->option('clear-cache')) {
            $this->line('🗑️  Limpiando caché de sincronización...');
            \Illuminate\Support\Facades\Cache::forget('sincronizacion_hora_actual');
            $this->line('✅ Caché limpiado');
            $this->newLine();
        }

        // Mostrar configuración
        $this->line('📋 Configuración:');
        $config = config('external_downloads.sincronizacion_hora', []);
        $this->line('   URLs a intentar: ' . count($config['urls'] ?? []) . ' APIs');
        foreach ($config['urls'] ?? [] as $idx => $url) {
            $this->line("   {$idx}. {$url}");
        }
        $this->line("   Timeout: {$config['timeout']}s");
        $this->line("   Caché TTL: {$config['cache_ttl_minutes']}min");
        $this->newLine();

        // Testear conectividad básica
        $this->line('🌐 Test de conectividad:');
        $urls = $config['urls'] ?? [];
        foreach ($urls as $idx => $url) {
            $this->testUrl($url, $idx);
        }
        $this->newLine();

        // Sincronizar hora
        $this->line('⏰ Sincronizando hora...');
        $horaService = app(SincronizacionHoraService::class);
        $startTime = microtime(true);
        $result = $horaService->obtener();
        $duration = (microtime(true) - $startTime) * 1000;

        $this->line("   Duración: {$duration}ms");
        $this->newLine();

        // Mostrar resultado
        $this->line('📊 Resultado:');
        $this->line("   Éxito: " . ($result['success'] ? '✅ Sí' : '❌ No'));
        $this->line("   Sincronizado: " . ($result['synced'] ? '✅ Sí' : '❌ No (usando fallback)'));
        $this->line("   Fuente: " . $result['source']);
        $this->line("   Hora: " . $result['datetime']);
        $this->line("   Timezone: " . $result['timezone']);
        if ($result['drift_seconds'] !== null) {
            $this->line("   Drift: {$result['drift_seconds']}s");
        }
        $this->newLine();

        // Mostrar logs
        if ($this->option('debug')) {
            $this->line('📝 Logs recientes de sincronización:');
            $this->line('   (Ver en storage/logs/laravel.log)');
            $this->newLine();
            $this->line('   Ejecuta: tail -f storage/logs/laravel.log | grep "SincronizacionHoraService"');
        }

        // Diagnóstico
        $this->line('🔍 Diagnóstico:');
        if ($result['synced']) {
            $this->info('   ✅ Sincronización exitosa');
        } else {
            $this->warn('   ⚠️  Sincronización fallida - Posibles causas:');
            $this->line('   • Firewall/Proxy corporativo bloqueando las APIs');
            $this->line('   • Conexión a Internet lenta o inestable');
            $this->line('   • APIs externas no disponibles');
            $this->newLine();
            $this->line('   Soluciones:');
            $this->line('   1. Ejecutar: php artisan external:test-connectivity');
            $this->line('   2. Verificar configuración de proxy en .env');
            $this->line('   3. Revisar logs: tail storage/logs/laravel.log');
            $this->line('   4. Ejecutar con --debug: php artisan external:debug-hora --debug');
        }

        $this->info('═══════════════════════════════════════════════════════════════');
    }

    protected function testUrl(string $url, int $idx): void
    {
        try {
            $startTime = microtime(true);
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            $duration = (microtime(true) - $startTime) * 1000;

            if ($response->successful()) {
                $this->info("   ✅ URL {$idx}: OK ({$duration}ms)");
            } else {
                $this->warn("   ⚠️  URL {$idx}: HTTP {$response->status()} ({$duration}ms)");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ URL {$idx}: {$e->getMessage()}");
        }
    }
}
