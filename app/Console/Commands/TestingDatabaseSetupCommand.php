<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\DatabaseSafetyChecker;

/**
 * Comando para crear y configurar la base de datos de testing
 * 
 * Uso:
 *   php artisan testing:db-setup
 *   php artisan testing:db-setup --fresh  (recrea desde cero)
 */
class TestingDatabaseSetupCommand extends Command
{
    protected $signature = 'testing:db-setup 
                            {--fresh : Elimina y recrea la base de datos desde cero}
                            {--force : Fuerza la ejecución sin confirmación}';

    protected $description = 'Crea y configura la base de datos para testing (tesoreria_oficinas_test)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  CONFIGURACIÓN DE BASE DE DATOS DE TESTING');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('');

        // Verificar configuración de seguridad
        $this->info('Verificando configuración de seguridad...');
        $safetyCheck = DatabaseSafetyChecker::check();

        if (!$safetyCheck['safe']) {
            $this->error('❌ La configuración NO es segura. Abortando.');
            $this->error('');
            foreach ($safetyCheck['messages'] as $message) {
                $this->warn($message);
            }
            $this->error('');
            $this->error('Ejecuta primero: php artisan testing:safety-check');
            return Command::FAILURE;
        }

        $this->info('✓ Configuración de seguridad correcta');
        $this->info('');

        $dbName = config('database.connections.' . config('database.default') . '.database');
        $dbHost = config('database.connections.' . config('database.default') . '.host');
        $dbPort = config('database.connections.' . config('database.default') . '.port');

        $this->table(
            ['Configuración', 'Valor'],
            [
                ['Base de datos', $dbName],
                ['Host', $dbHost],
                ['Puerto', $dbPort],
                ['Usuario', config('database.connections.' . config('database.default') . '.username')],
            ]
        );

        // Verificar si la BD ya existe
        $dbExists = $this->databaseExists($dbName);

        if ($dbExists) {
            $this->info("✓ La base de datos '{$dbName}' ya existe");
            
            if ($this->option('fresh')) {
                if (!$this->option('force')) {
                    if (!$this->confirm('¿Deseas ELIMINAR y recrear la base de datos? Se perderán todos los datos de testing.')) {
                        $this->info('Operación cancelada.');
                        return Command::SUCCESS;
                    }
                }

                $this->warn('Eliminando base de datos...');
                $this->dropDatabase($dbName);
                $this->info('✓ Base de datos eliminada');
                $dbExists = false;
            }
        }

        // Crear la base de datos si no existe
        if (!$dbExists) {
            $this->info("Creando base de datos '{$dbName}'...");
            $this->createDatabase($dbName);
            $this->info('✓ Base de datos creada');
        }

        // Ejecutar migraciones
        $this->info('');
        $this->info('Ejecutando migraciones...');
        
        $exitCode = Artisan::call('migrate', [
            '--env' => 'testing',
            '--force' => true,
        ]);

        if ($exitCode === 0) {
            $this->info('✓ Migraciones ejecutadas correctamente');
        } else {
            $this->error('❌ Error al ejecutar migraciones');
            return Command::FAILURE;
        }

        // Contar tablas
        $tableCount = $this->countTables();
        $this->info("✓ Base de datos configurada con {$tableCount} tablas");

        // Resumen
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line('<fg=green;options=bold>  ✓ BASE DE DATOS DE TESTING LISTA</>');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('');
        $this->info('Próximos pasos:');
        $this->comment('  1. Ejecutar tests: php artisan test');
        $this->comment('  2. Ver coverage: php artisan test --coverage');
        $this->comment('  3. Test específico: php artisan test --filter=NombreTest');
        $this->info('');

        return Command::SUCCESS;
    }

    /**
     * Verifica si la base de datos existe
     */
    private function databaseExists(string $dbName): bool
    {
        try {
            $connection = config('database.default');
            $host = config("database.connections.{$connection}.host");
            $port = config("database.connections.{$connection}.port");
            $username = config("database.connections.{$connection}.username");
            $password = config("database.connections.{$connection}.password");

            $pdo = new \PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password
            );

            $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbName}'");
            return $result && $result->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Crea la base de datos
     */
    private function createDatabase(string $dbName): void
    {
        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        $pdo = new \PDO(
            "mysql:host={$host};port={$port}",
            $username,
            $password
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Elimina la base de datos
     */
    private function dropDatabase(string $dbName): void
    {
        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        $pdo = new \PDO(
            "mysql:host={$host};port={$port}",
            $username,
            $password
        );

        $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
    }

    /**
     * Cuenta las tablas en la base de datos
     */
    private function countTables(): int
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
