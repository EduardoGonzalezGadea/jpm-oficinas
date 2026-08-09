<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->assertTestingDatabase($app);

        return $app;
    }

    /**
     * CANDADO DE SEGURIDAD: aborta la ejecución ANTES de que RefreshDatabase
     * (o cualquier hook) toque la base de datos si el entorno de testing apunta
     * a una base considerada de producción/desarrollo.
     */
    protected function assertTestingDatabase($app): void
    {
        $connection = $app['config']->get('database.default');
        $dbName = $app['config']->get("database.connections.{$connection}.database");

        $productionDbs = array_map('strtolower', ['tesoreria_oficinas']);

        if (in_array(strtolower((string) $dbName), $productionDbs)) {
            throw new \RuntimeException(
                'SEGURO DE SEGURIDAD DISPARADO: los tests apuntan a la base de datos "'
                . $dbName . '" (producción/desarrollo) en lugar de "tesoreria_oficinas_test". '
                . 'NOTA: si existe bootstrap/cache/config.php cacheado, eliminalo con '
                . '"php artisan config:clear" y vuelve a ejecutar. No se ejecutó ninguna migración.'
            );
        }
    }
}