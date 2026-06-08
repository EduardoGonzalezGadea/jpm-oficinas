<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\PrendasExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para PrendasExtractor.
 */
class PrendasExtractorTest extends TestCase
{
    private PrendasExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new PrendasExtractor();
    }

    // -------------------------------------------------------------------------
    // soporta()
    // -------------------------------------------------------------------------

    public function test_soporta_prendas(): void
    {
        $this->assertTrue($this->extractor->soporta('prendas'));
        $this->assertTrue($this->extractor->soporta('prenda'));
    }

    public function test_no_soporta_otros(): void
    {
        $this->assertFalse($this->extractor->soporta('multas_cobradas'));
        $this->assertFalse($this->extractor->soporta('arrendamientos'));
    }

    // -------------------------------------------------------------------------
    // getNombreLegible()
    // -------------------------------------------------------------------------

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Prendas', $this->extractor->getNombreLegible());
    }

    // -------------------------------------------------------------------------
    // extraer()
    // -------------------------------------------------------------------------

    private function textoPrendaEjemplo(): string
    {
        return <<<'TEXT'
e-Ticket Contado
F 33001 Contado
NOMBRE O DENOMINACIÓN DOMICILIO FISCAL
ANA MARTINEZ GOMEZ
INFORMACION ADICIONAL
C.I.: 3.210.987-6
FECHA MONEDA
08/02/2026 Peso uruguayo

DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
INSCRIPCION DE PRENDA VEHICULO AA 5678 CORRESPONDE A TRAMITE 1 600,00 600,00

MONTO NO FACTURABLE: 600,00
TOTAL A PAGAR: 600,00
Efectivo: 600,00
REFERENCIAS:
TEXT;
    }

    private function textoSinPrenda(): string
    {
        return <<<'TEXT'
e-Ticket Contado
G 44001 Contado
FECHA MONEDA
01/01/2026 Peso uruguayo
DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
SERVICIO GENERICO 1 200,00 200,00
MONTO NO FACTURABLE: 200,00
TOTAL A PAGAR: 200,00
TEXT;
    }

    public function test_extrae_fecha(): void
    {
        $datos = $this->extractor->extraer($this->textoPrendaEjemplo());
        $this->assertEquals('08/02/2026', $datos['fecha']);
    }

    public function test_extrae_serie_numero(): void
    {
        $datos = $this->extractor->extraer($this->textoPrendaEjemplo());
        $this->assertEquals('F', $datos['serie']);
        $this->assertEquals('33001', $datos['numero']);
    }

    public function test_extrae_monto(): void
    {
        $datos = $this->extractor->extraer($this->textoPrendaEjemplo());
        $this->assertEquals(600.0, $datos['monto']);
    }

    public function test_texto_sin_prenda_retorna_error_validacion(): void
    {
        $datos = $this->extractor->extraer($this->textoSinPrenda());
        $this->assertArrayHasKey('error_validacion', $datos);
    }

    // -------------------------------------------------------------------------
    // validar()
    // -------------------------------------------------------------------------

    public function test_validar_completo(): void
    {
        $datos = [
            'fecha'  => '08/02/2026',
            'serie'  => 'F',
            'numero' => '33001',
            'monto'  => 600.0,
        ];
        $resultado = $this->extractor->validar($datos);
        $this->assertTrue($resultado['valid']);
    }

    public function test_validar_con_error_validacion_es_invalido(): void
    {
        $datos = [
            'error_validacion' => 'Sin palabra prenda',
            'fecha'            => '',
            'serie'            => '',
            'numero'           => '',
            'monto'            => 0,
        ];
        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
    }
}
