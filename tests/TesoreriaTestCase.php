<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithTesoreria;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithFakeHttpResponses;

/**
 * TesoreriaTestCase
 * 
 * Clase base para todos los tests de módulos de Tesorería.
 * Incluye helpers específicos del dominio y configuración común.
 * 
 * IMPORTANTE: Esta clase usa RefreshDatabase por defecto, lo que significa
 * que la base de datos se migrará y limpiará antes de cada test.
 * 
 * Todos los tests que hereden de esta clase trabajarán con una BD limpia
 * y NUNCA tocarán la base de datos de producción gracias a las protecciones
 * implementadas en DatabaseProtection y CreatesApplication.
 */
abstract class TesoreriaTestCase extends TestCase
{
    use RefreshDatabase;
    use InteractsWithTesoreria;
    use WithAuthentication;
    use WithFakeHttpResponses;

    /**
     * Indica que estos tests requieren base de datos
     */
    protected bool $requiresDatabase = true;

    /**
     * Configuración que se ejecuta antes de cada test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar datos básicos de Tesorería para todos los tests
        $this->setupDatosBasicosTesoreria();
    }

    /**
     * Helper: Assert que un asiento de libro diario fue creado
     */
    protected function assertAsientoCreado(array $attributes): void
    {
        $this->assertDatabaseHas('tes_libro_diario', $attributes);
    }

    /**
     * Helper: Assert que un asiento de libro diario NO fue creado
     */
    protected function assertAsientoNoCreado(array $attributes): void
    {
        $this->assertDatabaseMissing('tes_libro_diario', $attributes);
    }

    /**
     * Helper: Assert que una caja chica fue creada
     */
    protected function assertCajaChicaCreada(array $attributes): void
    {
        $this->assertDatabaseHas('tes_caja_chica', $attributes); // Corregido: era tes_cch_cajas
    }

    /**
     * Helper: Assert que un pago fue creado
     */
    protected function assertPagoCreado(array $attributes): void
    {
        $this->assertDatabaseHas('tes_cch_pagos', $attributes);
    }

    /**
     * Helper: Assert que un pendiente fue creado
     */
    protected function assertPendienteCreado(array $attributes): void
    {
        $this->assertDatabaseHas('tes_cch_pendientes', $attributes);
    }

    /**
     * Helper: Assert que un CFE fue creado
     */
    protected function assertCfeCreado(array $attributes): void
    {
        $this->assertDatabaseHas('tes_cfes', $attributes); // Corregido: era tes_cfe
    }

    /**
     * Helper: Assert que una multa fue creada
     */
    protected function assertMultaCreada(array $attributes): void
    {
        $this->assertDatabaseHas('tes_multas_cobradas', $attributes);
    }

    /**
     * Helper: Assert que el saldo de una subcuenta es correcto
     */
    protected function assertSaldoSubcuenta(int $detalleId, float $saldoEsperado): void
    {
        $asiento = \App\Models\Tesoreria\LibroDiario::where('detalle_id', $detalleId)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$asiento) {
            $this->fail("No se encontró ningún asiento para el detalle_id {$detalleId}");
        }

        $this->assertFloatEquals(
            $saldoEsperado,
            $asiento->saldo,
            0.01,
            "El saldo de la subcuenta {$detalleId} no es el esperado"
        );
    }

    /**
     * Helper: Obtiene el saldo actual de una subcuenta
     */
    protected function getSaldoSubcuenta(int $detalleId): float
    {
        $asiento = \App\Models\Tesoreria\LibroDiario::where('detalle_id', $detalleId)
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $asiento ? $asiento->saldo : 0;
    }

    /**
     * Helper: Assert que un asiento está confirmado
     */
    protected function assertAsientoConfirmado(int $asientoId): void
    {
        $this->assertDatabaseHas('tes_libro_diario', [
            'id' => $asientoId,
            'confirmado' => true,
        ]);
    }

    /**
     * Helper: Assert que un asiento NO está confirmado
     */
    protected function assertAsientoNoConfirmado(int $asientoId): void
    {
        $this->assertDatabaseHas('tes_libro_diario', [
            'id' => $asientoId,
            'confirmado' => false,
        ]);
    }

    /**
     * Helper: Cuenta asientos de libro diario que cumplen criterios
     */
    protected function contarAsientos(array $criteria): int
    {
        return \App\Models\Tesoreria\LibroDiario::where($criteria)->count();
    }

    /**
     * Helper: Assert que se creó exactamente N asientos
     */
    protected function assertAsientosCount(int $expected, array $criteria = []): void
    {
        $actual = empty($criteria) 
            ? \App\Models\Tesoreria\LibroDiario::count()
            : $this->contarAsientos($criteria);

        $this->assertEquals(
            $expected,
            $actual,
            "Se esperaban {$expected} asientos, pero se encontraron {$actual}"
        );
    }

    /**
     * Helper: Verifica que un monto está en el rango esperado (útil para cálculos con decimales)
     */
    protected function assertMontoInRange(float $actual, float $expected, float $tolerance = 0.01): void
    {
        $min = $expected - $tolerance;
        $max = $expected + $tolerance;
        
        $this->assertInRange(
            $actual,
            $min,
            $max,
            "El monto {$actual} no está en el rango esperado [{$min}, {$max}]"
        );
    }

    /**
     * Helper: Mock del parser de CFE para tests
     */
    protected function mockCfeParser(array $extractedData): void
    {
        $mock = \Mockery::mock(\App\Services\CfeProcessorService::class)->makePartial();
        $mock->shouldReceive('procesarPdf')->andReturn($extractedData);
        $this->app->instance(\App\Services\CfeProcessorService::class, $mock);
    }

    /**
     * Helper: Crea un archivo PDF fake para tests de CFE
     */
    protected function createFakePdfFile(string $filename = 'test.pdf'): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create($filename, 100, 'application/pdf');
    }

    /**
     * Helper: Assert que un archivo fue guardado en storage
     */
    protected function assertFileStoraged(string $path): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Storage::exists($path),
            "El archivo '{$path}' no fue guardado en storage"
        );
    }

    /**
     * Helper: Limpia archivos de storage creados durante tests
     */
    protected function cleanTestStorage(string $directory = 'test'): void
    {
        \Illuminate\Support\Facades\Storage::deleteDirectory($directory);
    }
}
