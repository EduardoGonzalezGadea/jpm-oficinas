<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * DatabaseSafetyChecker
 * 
 * Utilidad para verificar la seguridad de la base de datos antes de ejecutar tests.
 * Puede ser ejecutado manualmente desde la línea de comandos o integrado en CI/CD.
 */
class DatabaseSafetyChecker
{
    /**
     * Bases de datos de producción que NUNCA deben ser tocadas
     */
    private const PRODUCTION_DATABASES = [
        'tesoreria_oficinas',
        'oficinas',
        'tesoreria',
    ];

    /**
     * Base de datos correcta para tests
     */
    private const TEST_DATABASE = 'tesoreria_oficinas_test';

    /**
     * Verifica que la configuración de testing es segura
     * 
     * @return array Array con 'safe' => bool y 'messages' => array
     */
    public static function check(): array
    {
        $messages = [];
        $safe = true;

        // 1. Verificar ambiente
        $env = app()->environment();
        if ($env !== 'testing') {
            $safe = false;
            $messages[] = "❌ PELIGRO: APP_ENV es '{$env}', debería ser 'testing'";
        } else {
            $messages[] = "✓ APP_ENV correcto: testing";
        }

        // 2. Verificar nombre de base de datos
        $dbName = config('database.connections.' . config('database.default') . '.database');
        if (self::isProductionDatabase($dbName)) {
            $safe = false;
            $messages[] = "❌ PELIGRO CRÍTICO: La base de datos configurada es '{$dbName}' (PRODUCCIÓN)";
            $messages[] = "   Los tests DESTRUIRÍAN los datos de producción.";
        } elseif ($dbName !== self::TEST_DATABASE) {
            $safe = false;
            $messages[] = "⚠️  ADVERTENCIA: La base de datos es '{$dbName}', debería ser '" . self::TEST_DATABASE . "'";
        } else {
            $messages[] = "✓ Base de datos correcta: " . self::TEST_DATABASE;
        }

        // 3. Verificar configuración de phpunit.xml
        if (file_exists(base_path('phpunit.xml'))) {
            $phpunitXml = file_get_contents(base_path('phpunit.xml'));
            
            if (!str_contains($phpunitXml, self::TEST_DATABASE)) {
                $safe = false;
                $messages[] = "⚠️  phpunit.xml no contiene '" . self::TEST_DATABASE . "'";
            } else {
                $messages[] = "✓ phpunit.xml configurado correctamente";
            }

            if (str_contains($phpunitXml, 'tesoreria_oficinas"')) {
                $safe = false;
                $messages[] = "❌ phpunit.xml contiene 'tesoreria_oficinas' (BD de producción)";
            }
        }

        // 4. Verificar .env.testing
        if (file_exists(base_path('.env.testing'))) {
            $envTesting = file_get_contents(base_path('.env.testing'));
            
            if (!str_contains($envTesting, self::TEST_DATABASE)) {
                $safe = false;
                $messages[] = "⚠️  .env.testing no contiene '" . self::TEST_DATABASE . "'";
            } else {
                $messages[] = "✓ .env.testing configurado correctamente";
            }

            if (str_contains($envTesting, 'tesoreria_oficinas')) {
                $safe = false;
                $messages[] = "❌ .env.testing contiene 'tesoreria_oficinas' (BD de producción)";
            }
        } else {
            $messages[] = "⚠️  .env.testing no existe";
        }

        // 5. Verificar que la BD de test existe y es accesible
        try {
            DB::connection()->getPdo();
            $messages[] = "✓ Conexión a base de datos exitosa";
            
            // Verificar que está vacía o tiene estructura de tests
            $tableCount = count(DB::select('SHOW TABLES'));
            $messages[] = "ℹ️  La base de datos tiene {$tableCount} tablas";
            
        } catch (\Exception $e) {
            $safe = false;
            $messages[] = "❌ Error al conectar a la base de datos: " . $e->getMessage();
        }

        // 6. Verificar variables de entorno peligrosas
        $dangerousEnvVars = [
            'DB_DATABASE' => env('DB_DATABASE'),
        ];

        foreach ($dangerousEnvVars as $var => $value) {
            if (self::isProductionDatabase($value)) {
                $messages[] = "⚠️  Variable de entorno {$var} apunta a producción: {$value}";
                $messages[] = "   (Esto puede ser sobreescrito por phpunit.xml, pero es peligroso)";
            }
        }

        return [
            'safe' => $safe,
            'messages' => $messages,
            'database' => $dbName,
            'environment' => $env,
        ];
    }

    /**
     * Verifica si un nombre de base de datos es de producción
     */
    private static function isProductionDatabase(?string $dbName): bool
    {
        if (empty($dbName)) {
            return false;
        }

        $normalized = strtolower(trim($dbName));
        
        foreach (self::PRODUCTION_DATABASES as $prodDb) {
            if (strtolower($prodDb) === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * Imprime el reporte de seguridad
     */
    public static function report(): void
    {
        echo "\n";
        echo "=================================================================\n";
        echo "  VERIFICACIÓN DE SEGURIDAD DE BASE DE DATOS PARA TESTS\n";
        echo "=================================================================\n";
        echo "\n";

        $result = self::check();

        foreach ($result['messages'] as $message) {
            echo $message . "\n";
        }

        echo "\n";
        echo "=================================================================\n";
        
        if ($result['safe']) {
            echo "  ✓ SEGURO: Los tests pueden ejecutarse sin riesgo\n";
            echo "=================================================================\n";
            exit(0);
        } else {
            echo "  ❌ PELIGRO: NO ejecutar tests en esta configuración\n";
            echo "  Los datos de producción podrían ser destruidos.\n";
            echo "=================================================================\n";
            exit(1);
        }
    }

    /**
     * Ejecuta la verificación y lanza excepción si no es seguro
     */
    public static function assertSafe(): void
    {
        $result = self::check();
        
        if (!$result['safe']) {
            $errorMessage = implode("\n", $result['messages']);
            throw new \RuntimeException(
                "PROTECCIÓN DE SEGURIDAD: No es seguro ejecutar tests.\n\n" . $errorMessage
            );
        }
    }
}
