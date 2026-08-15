<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait DatabaseProtection
 * 
 * Proporciona capas adicionales de protección para asegurar que los tests
 * NUNCA toquen la base de datos de producción.
 * 
 * IMPORTANTE: Este trait es una capa adicional de seguridad además de la
 * verificación en CreatesApplication y TestCase::setUp()
 */
trait DatabaseProtection
{
    /**
     * Nombres de bases de datos de producción que NUNCA deben ser tocadas por tests
     */
    protected array $productionDatabases = [
        'tesoreria_oficinas',
        'oficinas',
        'tesoreria',
    ];

    /**
     * Verifica que NO estamos usando una base de datos de producción
     * 
     * @throws \RuntimeException si detecta una BD de producción
     */
    protected function assertNotProductionDatabase(): void
    {
        $dbName = $this->getCurrentDatabaseName();

        if ($this->isProductionDatabase($dbName)) {
            throw new \RuntimeException(
                "PROTECCIÓN DE SEGURIDAD ACTIVADA:\n" .
                "Se detectó que los tests están intentando usar la base de datos '{$dbName}' " .
                "que está marcada como base de datos de PRODUCCIÓN.\n\n" .
                "Los tests DEBEN usar 'tesoreria_oficinas_test'.\n\n" .
                "Soluciones:\n" .
                "1. Ejecutar: php artisan config:clear\n" .
                "2. Verificar que phpunit.xml tenga: <env name=\"DB_DATABASE\" value=\"tesoreria_oficinas_test\"/>\n" .
                "3. Verificar que .env.testing tenga: DB_DATABASE=tesoreria_oficinas_test\n" .
                "4. NO ejecutar tests con variables de entorno de producción\n\n" .
                "NINGÚN TEST SE EJECUTÓ. Los datos de producción están seguros."
            );
        }
    }

    /**
     * Obtiene el nombre de la base de datos actual
     */
    protected function getCurrentDatabaseName(): string
    {
        $connection = config('database.default');
        return (string) config("database.connections.{$connection}.database");
    }

    /**
     * Verifica si un nombre de base de datos es considerado de producción
     */
    protected function isProductionDatabase(?string $dbName): bool
    {
        if (empty($dbName)) {
            return false;
        }

        $normalized = strtolower(trim($dbName));
        $productionNormalized = array_map(function($db) {
            return strtolower(trim($db));
        }, $this->productionDatabases);

        return in_array($normalized, $productionNormalized, true);
    }

    /**
     * Verifica que estamos en el ambiente de testing correcto
     * 
     * @throws \RuntimeException si no estamos en ambiente de testing
     */
    protected function assertTestingEnvironment(): void
    {
        if (app()->environment() !== 'testing') {
            throw new \RuntimeException(
                "PROTECCIÓN DE SEGURIDAD ACTIVADA:\n" .
                "Los tests deben ejecutarse con APP_ENV=testing.\n" .
                "Ambiente actual: " . app()->environment() . "\n\n" .
                "Esto evita que los tests se ejecuten accidentalmente en producción."
            );
        }
    }

    /**
     * Verifica que la base de datos de testing existe
     * 
     * @return bool true si existe, false si no
     */
    protected function testDatabaseExists(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Proporciona información de debugging sobre la configuración de BD actual
     */
    protected function getDatabaseDebugInfo(): array
    {
        return [
            'app_env' => app()->environment(),
            'db_connection' => config('database.default'),
            'db_database' => $this->getCurrentDatabaseName(),
            'db_host' => config('database.connections.' . config('database.default') . '.host'),
            'db_port' => config('database.connections.' . config('database.default') . '.port'),
            'is_production_db' => $this->isProductionDatabase($this->getCurrentDatabaseName()),
            'db_exists' => $this->testDatabaseExists(),
        ];
    }

    /**
     * Imprime información de debugging de la base de datos
     */
    protected function dumpDatabaseInfo(): void
    {
        dump($this->getDatabaseDebugInfo());
    }

    /**
     * Assert que verifica que estamos usando la base de datos de testing correcta
     */
    protected function assertUsingTestDatabase(): void
    {
        $dbName = $this->getCurrentDatabaseName();
        
        $this->assertEquals(
            'tesoreria_oficinas_test',
            $dbName,
            "Los tests deben usar 'tesoreria_oficinas_test', pero están usando '{$dbName}'"
        );
    }

    /**
     * Verifica todas las protecciones de seguridad antes de ejecutar tests
     * 
     * Llama a este método en setUp() para máxima protección
     */
    protected function assertDatabaseSafety(): void
    {
        $this->assertTestingEnvironment();
        $this->assertNotProductionDatabase();
        $this->assertUsingTestDatabase();
    }
}
