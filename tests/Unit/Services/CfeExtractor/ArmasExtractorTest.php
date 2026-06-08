<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\ArmasExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para ArmasExtractor (Porte y Tenencia de Armas).
 * Usa los nombres de campo reales: receptor_documento, receptor_nombre, monto.
 */
class ArmasExtractorTest extends TestCase
{
    private ArmasExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new ArmasExtractor();
    }

    // -------------------------------------------------------------------------
    // soporta()
    // -------------------------------------------------------------------------

    public function test_soporta_porte_armas(): void
    {
        $this->assertTrue($this->extractor->soporta('porte_armas'));
    }

    public function test_soporta_tenencia_armas(): void
    {
        $this->assertTrue($this->extractor->soporta('tenencia_armas'));
    }

    public function test_soporta_arma(): void
    {
        $this->assertTrue($this->extractor->soporta('arma'));
    }

    public function test_soporta_tahta(): void
    {
        $this->assertTrue($this->extractor->soporta('tahta'));
    }

    public function test_no_soporta_otros_tipos(): void
    {
        $this->assertFalse($this->extractor->soporta('multas_cobradas'));
        $this->assertFalse($this->extractor->soporta('arrendamientos'));
        $this->assertFalse($this->extractor->soporta('prendas'));
    }

    // -------------------------------------------------------------------------
    // getNombreLegible()
    // -------------------------------------------------------------------------

    public function test_nombre_legible_contiene_arma(): void
    {
        $nombre = $this->extractor->getNombreLegible();
        $this->assertStringContainsStringIgnoringCase('arma', $nombre);
    }

    // -------------------------------------------------------------------------
    // extraer() — el extractor usa 'receptor_documento' y 'receptor_nombre'
    // -------------------------------------------------------------------------

    private function textoArmasEjemplo(): string
    {
        return <<<'TEXT'
e-Ticket Contado
E 77001 Contado
NOMBRE O DENOMINACIÓN DOMICILIO FISCAL
CARLOS RODRIGUEZ SUAREZ
INFORMACION ADICIONAL
C.I.: 4.321.098-7
FECHA MONEDA
05/03/2026 Peso uruguayo

DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
PORTE DE ARMA CATEGORIA A CORRESPONDE A HABILITACION 1 450,00 450,00

MONTO NO FACTURABLE: 450,00
TOTAL A PAGAR: 450,00
Efectivo: 450,00
REFERENCIAS:
TEXT;
    }

    public function test_extrae_fecha(): void
    {
        $datos = $this->extractor->extraer($this->textoArmasEjemplo());
        $this->assertEquals('05/03/2026', $datos['fecha']);
    }

    public function test_extrae_serie_y_numero(): void
    {
        $datos = $this->extractor->extraer($this->textoArmasEjemplo());
        $this->assertEquals('E', $datos['serie']);
        $this->assertEquals('77001', $datos['numero']);
    }

    /**
     * ArmasExtractor usa 'receptor_documento' (no 'cedula').
     */
    public function test_extrae_receptor_documento(): void
    {
        $datos = $this->extractor->extraer($this->textoArmasEjemplo());
        $this->assertArrayHasKey('receptor_documento', $datos);
        $this->assertEquals('4.321.098-7', $datos['receptor_documento']);
    }

    public function test_extrae_monto(): void
    {
        $datos = $this->extractor->extraer($this->textoArmasEjemplo());
        $this->assertEquals(450.0, $datos['monto']);
    }

    public function test_extrae_moneda_uyu(): void
    {
        $datos = $this->extractor->extraer($this->textoArmasEjemplo());
        $this->assertEquals('UYU', $datos['moneda']);
    }

    // -------------------------------------------------------------------------
    // determinarTipoArma()
    // -------------------------------------------------------------------------

    public function test_determina_porte(): void
    {
        $tipo = $this->extractor->determinarTipoArma('Autorización de PORTE de arma de fuego.');
        $this->assertEquals('porte_armas', $tipo);
    }

    public function test_determina_tenencia(): void
    {
        $tipo = $this->extractor->determinarTipoArma('Habilitación TENENCIA de arma larga.');
        $this->assertEquals('tenencia_armas', $tipo);
    }

    public function test_determina_tahta_como_tenencia(): void
    {
        $tipo = $this->extractor->determinarTipoArma('Habilitación TAHTA número 12345.');
        $this->assertEquals('tenencia_armas', $tipo);
    }

    // -------------------------------------------------------------------------
    // validar()
    // -------------------------------------------------------------------------

    public function test_validar_datos_completos(): void
    {
        $datos = [
            'fecha'  => '05/03/2026',
            'serie'  => 'E',
            'numero' => '77001',
            'monto'  => 450.0,
        ];
        $resultado = $this->extractor->validar($datos);
        $this->assertTrue($resultado['valid']);
    }

    public function test_validar_monto_cero_es_invalido(): void
    {
        $datos = [
            'fecha'  => '05/03/2026',
            'serie'  => 'E',
            'numero' => '77001',
            'monto'  => 0.0,
        ];
        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
        $this->assertContains('Monto no valido', $resultado['errors']);
    }
}
