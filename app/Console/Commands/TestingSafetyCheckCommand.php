<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tests\Helpers\DatabaseSafetyChecker;

/**
 * Comando para verificar que la configuración de testing es segura
 * antes de ejecutar los tests.
 * 
 * Uso:
 *   php artisan testing:safety-check
 * 
 * Este comando verifica que:
 * - El ambiente es 'testing'
 * - La base de datos NO es de producción
 * - La configuración apunta a 'tesoreria_oficinas_test'
 * - Los archivos de configuración son correctos
 */
class TestingSafetyCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testing:safety-check 
                            {--verbose : Mostrar información detallada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica que la configuración de testing es segura y no afectará la base de datos de producción';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║   VERIFICACIÓN DE SEGURIDAD DE BASE DE DATOS PARA TESTS      ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');

        $result = DatabaseSafetyChecker::check();

        // Mostrar cada mensaje con el estilo apropiado
        foreach ($result['messages'] as $message) {
            if (str_starts_with($message, '✓')) {
                $this->line("<fg=green>{$message}</>");
            } elseif (str_starts_with($message, '❌')) {
                $this->line("<fg=red;options=bold>{$message}</>");
            } elseif (str_starts_with($message, '⚠️')) {
                $this->line("<fg=yellow>{$message}</>");
            } elseif (str_starts_with($message, 'ℹ️')) {
                $this->line("<fg=cyan>{$message}</>");
            } else {
                $this->line($message);
            }
        }

        $this->info('');

        if ($this->option('verbose')) {
            $this->info('Información adicional:');
            $this->table(
                ['Configuración', 'Valor'],
                [
                    ['Ambiente', $result['environment']],
                    ['Base de datos', $result['database']],
                    ['Conexión', config('database.default')],
                    ['Host', config('database.connections.' . config('database.default') . '.host')],
                    ['Puerto', config('database.connections.' . config('database.default') . '.port')],
                ]
            );
        }

        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        
        if ($result['safe']) {
            $this->line('<fg=green;options=bold>║  ✓ SEGURO: Los tests pueden ejecutarse sin riesgo            ║</>');
            $this->info('╚═══════════════════════════════════════════════════════════════╝');
            $this->info('');
            $this->info('Puedes ejecutar los tests con:');
            $this->comment('  php artisan test');
            $this->comment('  php artisan test --filter=NombreDelTest');
            $this->info('');
            return Command::SUCCESS;
        } else {
            $this->line('<fg=red;options=bold>║  ❌ PELIGRO: NO ejecutar tests en esta configuración         ║</>');
            $this->line('<fg=red;options=bold>║  Los datos de producción podrían ser destruidos.             ║</>');
            $this->info('╚═══════════════════════════════════════════════════════════════╝');
            $this->error('');
            $this->error('SOLUCIÓN:');
            $this->warn('1. Ejecuta: php artisan config:clear');
            $this->warn('2. Verifica que phpunit.xml tenga: DB_DATABASE=tesoreria_oficinas_test');
            $this->warn('3. Verifica que .env.testing tenga: DB_DATABASE=tesoreria_oficinas_test');
            $this->warn('4. NO uses variables de entorno de producción al ejecutar tests');
            $this->error('');
            return Command::FAILURE;
        }
    }
}
