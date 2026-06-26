<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $dbName = config("database.connections.{$connection}.database");

        $productionDbs = ['tesoreria_oficinas'];

        if (in_array($dbName, $productionDbs)) {
            throw new \RuntimeException(
                'SEGURO DE SEGURIDAD: Los tests están apuntando a la BD de desarrollo "'
                . $dbName . '". Se aborta la ejecución para evitar pérdida de datos. '
                . 'Configura .env.testing o phpunit.xml para usar "tesoreria_oficinas_test".'
            );
        }
    }
}
