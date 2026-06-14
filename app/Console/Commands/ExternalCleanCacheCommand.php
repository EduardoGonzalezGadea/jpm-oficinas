<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ExternalCleanCacheCommand extends Command
{
    protected $signature = 'external:clean-cache
                            {--all : Limpiar todo (UR, Hora, SOA)}
                            {--ur : Limpiar solo UR}
                            {--hora : Limpiar solo Hora}
                            {--soa : Limpiar solo SOA}
                            {--circuit-breaker : Limpiar circuit breakers}';

    protected $description = 'Limpia el caché de descargas externas y reinicia servicios';

    public function handle()
    {
        $this->info('🗑️  Limpiando caché de descargas externas...');
        $this->newLine();

        $all = $this->option('all') || !($this->option('ur') || $this->option('hora') || $this->option('soa') || $this->option('circuit-breaker'));

        $count = 0;

        // Limpiar UR
        if ($all || $this->option('ur')) {
            Cache::forget('valor_ur_completo');
            Cache::forget('valor_ur_ultimo_valido');
            $this->line('✅ Caché de UR limpiado');
            $count += 2;
        }

        // Limpiar Hora
        if ($all || $this->option('hora')) {
            Cache::forget('sincronizacion_hora_actual');
            $this->line('✅ Caché de Hora limpiado');
            $count++;
        }

        // Limpiar SOA
        if ($all || $this->option('soa')) {
            Cache::forget('valores_soa_completo');
            $this->line('✅ Caché de SOA limpiado');
            $count++;
        }

        // Limpiar circuit breakers
        if ($all || $this->option('circuit-breaker')) {
            $this->cleanCircuitBreakers();
            $this->line('✅ Circuit breakers reseteados');
        }

        $this->newLine();
        $this->info("🎉 Se limpió {$count} entradas de caché");
        $this->newLine();
        $this->line('Próximas cargas sincronizarán nuevamente con las APIs externas.');
    }

    protected function cleanCircuitBreakers(): void
    {
        $services = ['valor_ur', 'sincronizacion_hora', 'valores_soa'];
        $prefix = 'external_downloads_circuit_';

        foreach ($services as $service) {
            Cache::forget($prefix . $service . '_open');
            Cache::forget($prefix . $service . '_failures');
        }
    }
}
