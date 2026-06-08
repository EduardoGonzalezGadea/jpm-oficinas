<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\MultasExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para MultasExtractor.
 * Verifica extracción de datos y validación sin necesidad de base de datos.
 */
class MultasExtractorTest extends TestCase
{
    private MultasExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new MultasExtractor();
    }

    // -------------------------------------------------------------------------
    // soporta()
    // -------------------------------------------------------------------------

    public function test_soporta_multas_cobradas(): void
    {
        $this->assertTrue($this->extractor->soporta('multas_cobradas'));
    }

    public function test_soporta_multa(): void
    {
        $this->assertTrue($this->extractor->soporta('multa'));
    }

    public function test_soporta_infraccion(): void
    {
        $this->assertTrue($this->extractor->soporta('infraccion'));
    }

    public function test_no_soporta_tipo_ajeno(): void
    {
        $this->assertFalse($this->extractor->soporta('arrendamientos'));
        $this->assertFalse($this->extractor->soporta('prendas'));
        $this->assertFalse($this->extractor->soporta('eventuales'));
    }

    // -------------------------------------------------------------------------
    // getNombreLegible()
    // -------------------------------------------------------------------------

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Multas Cobradas', $this->extractor->getNombreLegible());
    }

    // -------------------------------------------------------------------------
    // extraer() — campos del texto de un CFE de multa sintético
    // -------------------------------------------------------------------------

    private function textoMultaEjemplo(): string
    {
        return <<<'TEXT'
e-Ticket Contado
A 123456 Contado
NOMBRE O DENOMINACIÓN DOMICILIO FISCAL
JUAN PEREZ RODRIGUEZ
INFORMACION ADICIONAL
C.I.: 1.234.567-8
FECHA MONEDA
15/03/2026 Peso uruguayo

DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
MULTA CARECER DE SOA CORRESPONDE A INFRACCION 001 1 1.500,00 1.500,00

MONTO NO FACTURABLE: 1.500,00
TOTAL A PAGAR: 1.500,00
Efectivo: 1.500,00
REFERENCIAS:
TEXT;
    }

    public function test_extrae_fecha(): void
    {
        $datos = $this->extractor->extraer($this->textoMultaEjemplo());
        $this->assertEquals('15/03/2026', $datos['fecha']);
    }

    public function test_extrae_serie_y_numero(): void
    {
        $datos = $this->extractor->extraer($this->textoMultaEjemplo());
        $this->assertEquals('A', $datos['serie']);
        $this->assertEquals('123456', $datos['numero']);
    }

    public function test_extrae_moneda_uyp(): void
    {
        $datos = $this->extractor->extraer($this->textoMultaEjemplo());
        $this->assertEquals('UYU', $datos['moneda']);
    }

    public function test_extrae_cedula_receptor(): void
    {
        $datos = $this->extractor->extraer($this->textoMultaEjemplo());
        $this->assertEquals('1.234.567-8', $datos['cedula']);
    }

    public function test_extrae_items_no_vacio(): void
    {
        $datos = $this->extractor->extraer($this->textoMultaEjemplo());
        $this->assertIsArray($datos['items']);
        $this->assertNotEmpty($datos['items']);
    }

    public function test_extrae_monto_total_como_float(): void
    {
        $datos = $this->extractor->extraer($this->textoMultaEjemplo());
        $this->assertIsFloat($datos['monto_total']);
        $this->assertEquals(1500.0, $datos['monto_total']);
    }

    // -------------------------------------------------------------------------
    // validar()
    // -------------------------------------------------------------------------

    public function test_validar_datos_completos_es_valido(): void
    {
        $datos = [
            'fecha'       => '15/03/2026',
            'serie'       => 'A',
            'numero'      => '123456',
            'monto'       => 1500.0,  // BaseExtractor::validar() busca 'monto'
            'monto_total' => 1500.0,
            'items'       => [
                ['detalle' => 'MULTA CARECER DE SOA', 'descripcion' => '', 'importe' => 1500.0],
            ],
        ];

        $resultado = $this->extractor->validar($datos);
        $this->assertTrue($resultado['valid']);
        $this->assertEmpty($resultado['errors']);
    }

    public function test_validar_sin_fecha_es_invalido(): void
    {
        $datos = [
            'fecha'       => '',
            'serie'       => 'A',
            'numero'      => '123456',
            'monto'       => 1500.0,
            'monto_total' => 1500.0,
            'items'       => [['detalle' => 'MULTA', 'descripcion' => '', 'importe' => 1500.0]],
        ];

        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
        $this->assertContains('Fecha no detectada', $resultado['errors']);
    }

    public function test_validar_sin_items_es_invalido(): void
    {
        $datos = [
            'fecha'       => '15/03/2026',
            'serie'       => 'A',
            'numero'      => '123456',
            'monto'       => 1500.0,
            'monto_total' => 1500.0,
            'items'       => [],
        ];

        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
        $this->assertContains('No se detectaron items en el CFE', $resultado['errors']);
    }

    public function test_validar_inconsistencia_monto_items_es_invalido(): void
    {
        $datos = [
            'fecha'       => '15/03/2026',
            'serie'       => 'A',
            'numero'      => '123456',
            'monto'       => 2000.0, // Para BaseExtractor
            'monto_total' => 2000.0, // No coincide con suma de items
            'items'       => [['detalle' => 'MULTA', 'descripcion' => '', 'importe' => 1500.0]],
        ];

        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
        $this->assertNotEmpty(array_filter($resultado['errors'], fn($e) => str_contains($e, 'Inconsistencia')));
    }
}
