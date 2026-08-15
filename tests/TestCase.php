<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\DatabaseProtection;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseProtection;

    /**
     * Indica si este test requiere base de datos
     * Los tests que no usen BD pueden sobreescribir esto a false para mejor rendimiento
     */
    protected bool $requiresDatabase = true;

    /**
     * Setup que se ejecuta antes de cada test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Protección triple de seguridad contra uso de BD de producción
        if ($this->requiresDatabase) {
            $this->assertDatabaseSafety();
        }
    }

    /**
     * Teardown que se ejecuta después de cada test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper para crear datos de prueba con factory
     * 
     * @param string $model Nombre del modelo
     * @param array $attributes Atributos a sobreescribir
     * @return mixed
     */
    protected function create(string $model, array $attributes = [])
    {
        $factoryMethod = 'create' . class_basename($model);
        
        if (method_exists($this, $factoryMethod)) {
            return $this->$factoryMethod($attributes);
        }

        // Fallback a factory si existe
        if (class_exists($model)) {
            return $model::factory()->create($attributes);
        }

        throw new \RuntimeException("No se encontró factory para modelo: {$model}");
    }

    /**
     * Helper para hacer (make) datos de prueba sin guardar en BD
     * 
     * @param string $model Nombre del modelo
     * @param array $attributes Atributos a sobreescribir
     * @return mixed
     */
    protected function make(string $model, array $attributes = [])
    {
        $factoryMethod = 'make' . class_basename($model);
        
        if (method_exists($this, $factoryMethod)) {
            return $this->$factoryMethod($attributes);
        }

        // Fallback a factory si existe
        if (class_exists($model)) {
            return $model::factory()->make($attributes);
        }

        throw new \RuntimeException("No se encontró factory para modelo: {$model}");
    }

    /**
     * Assert que un modelo existe en la base de datos
     */
    protected function assertModelExists($model): void
    {
        $this->assertDatabaseHas(
            $model->getTable(),
            [$model->getKeyName() => $model->getKey()]
        );
    }

    /**
     * Assert que un modelo NO existe en la base de datos
     */
    protected function assertModelDoesNotExist($model): void
    {
        $this->assertDatabaseMissing(
            $model->getTable(),
            [$model->getKeyName() => $model->getKey()]
        );
    }

    /**
     * Assert que un valor está dentro de un rango (útil para montos, fechas, etc.)
     */
    protected function assertInRange($value, $min, $max, string $message = ''): void
    {
        $this->assertGreaterThanOrEqual($min, $value, $message);
        $this->assertLessThanOrEqual($max, $value, $message);
    }

    /**
     * Assert que dos valores float/decimal son iguales (con tolerancia)
     */
    protected function assertFloatEquals(float $expected, float $actual, float $delta = 0.01, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected, $actual, $delta, $message);
    }

    /**
     * Assert que un array contiene ciertas claves
     */
    protected function assertArrayHasKeys(array $array, array $keys, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "El array no contiene la clave '{$key}'");
        }
    }

    /**
     * Assert que una colección no está vacía
     */
    protected function assertCollectionNotEmpty($collection, string $message = ''): void
    {
        $this->assertGreaterThan(0, count($collection), $message ?: 'La colección está vacía');
    }

    /**
     * Assert que una fecha está en formato DD/MM/YYYY
     */
    protected function assertDateFormat(string $date, string $message = ''): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d{2}\/\d{2}\/\d{4}$/',
            $date,
            $message ?: "La fecha '{$date}' no está en formato DD/MM/YYYY"
        );
    }

    /**
     * Assert que un monto está en formato uruguayo ($ 1.234,56)
     */
    protected function assertMontoFormatoUruguayo(string $monto, string $message = ''): void
    {
        $this->assertMatchesRegularExpression(
            '/^\$\s?\d{1,3}(\.\d{3})*,\d{2}$/',
            $monto,
            $message ?: "El monto '{$monto}' no está en formato uruguayo"
        );
    }

    /**
     * Helper para debugging: imprime el contenido de una respuesta JSON
     */
    protected function dumpJson($response): void
    {
        if (method_exists($response, 'json')) {
            dump($response->json());
        } else {
            dump($response);
        }
    }

    /**
     * Helper para debugging: imprime el contenido de la base de datos de una tabla
     */
    protected function dumpTable(string $table, int $limit = 10): void
    {
        dump(\Illuminate\Support\Facades\DB::table($table)->limit($limit)->get());
    }

    /**
     * Helper para debugging: imprime el último query SQL ejecutado
     */
    protected function dumpLastQuery(): void
    {
        dump(\Illuminate\Support\Facades\DB::getQueryLog());
    }

    /**
     * Habilita logging de queries SQL (útil para debugging)
     */
    protected function enableQueryLog(): void
    {
        \Illuminate\Support\Facades\DB::enableQueryLog();
    }

    /**
     * Obtiene el log de queries SQL ejecutadas
     */
    protected function getQueryLog(): array
    {
        return \Illuminate\Support\Facades\DB::getQueryLog();
    }
}
