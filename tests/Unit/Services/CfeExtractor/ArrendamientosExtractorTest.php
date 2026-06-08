<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\ArrendamientosExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para ArrendamientosExtractor.
 * Cubre soporta(), getNombreLegible(), extraer(), orden_cobro y validar().
 */
class ArrendamientosExtractorTest extends TestCase
{
    private ArrendamientosExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new ArrendamientosExtractor();
    }

    // -------------------------------------------------------------------------
    // soporta()
    // -------------------------------------------------------------------------

    public function test_soporta_arrendamiento(): void
    {
        $this->assertTrue($this->extractor->soporta('arrendamientos'));
        $this->assertTrue($this->extractor->soporta('arrendamiento'));
    }

    public function test_no_soporta_prendas(): void
    {
        $this->assertFalse($this->extractor->soporta('prendas'));
        $this->assertFalse($this->extractor->soporta('multas_cobradas'));
    }

    // -------------------------------------------------------------------------
    // getNombreLegible()
    // -------------------------------------------------------------------------

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Arrendamientos', $this->extractor->getNombreLegible());
    }

    // -------------------------------------------------------------------------
    // extraer()
    // -------------------------------------------------------------------------

    private function textoArrendamientoEjemplo(): string
    {
        return <<<'TEXT'
e-Factura Contado
C 99001 Contado
NOMBRE O DENOMINACIÓN DOMICILIO FISCAL
COMERCIO CENTRAL S.A.
INFORMACION ADICIONAL
RUT: 12.345.678-9
FECHA MONEDA
10/02/2026 Peso uruguayo

DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
ARRENDAMIENTO MES FEBRERO 2026 1 8.500,00 8.500,00

MONTO NO FACTURABLE: 8.500,00
TOTAL A PAGAR: 8.500,00
Transferencia: 8.500,00
REFERENCIAS:

ADENDA
ORDEN DE COBRO 55678
TEXT;
    }

    private function textoSinArrendamiento(): string
    {
        return <<<'TEXT'
e-Factura Contado
D 11111 Contado
FECHA MONEDA
01/01/2026 Peso uruguayo
DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
SERVICIO DE LIMPIEZA 1 5.000,00 5.000,00
MONTO NO FACTURABLE: 5.000,00
TOTAL A PAGAR: 5.000,00
REFERENCIAS:
TEXT;
    }

    public function test_extrae_fecha(): void
    {
        $datos = $this->extractor->extraer($this->textoArrendamientoEjemplo());
        $this->assertEquals('10/02/2026', $datos['fecha']);
    }

    public function test_extrae_serie_numero(): void
    {
        $datos = $this->extractor->extraer($this->textoArrendamientoEjemplo());
        $this->assertEquals('C', $datos['serie']);
        $this->assertEquals('99001', $datos['numero']);
    }

    public function test_extrae_monto(): void
    {
        $datos = $this->extractor->extraer($this->textoArrendamientoEjemplo());
        $this->assertEquals(8500.0, $datos['monto']);
    }

    public function test_extrae_orden_cobro_desde_adenda(): void
    {
        $datos = $this->extractor->extraer($this->textoArrendamientoEjemplo());
        $this->assertEquals('55678', $datos['orden_cobro']);
    }

    public function test_forma_pago_transferencia_se_agrega_al_detalle(): void
    {
        $datos = $this->extractor->extraer($this->textoArrendamientoEjemplo());
        // Con pago por transferencia, debe quedar en el detalle
        $this->assertStringContainsString('Transferencia', $datos['detalle']);
    }

    // -------------------------------------------------------------------------
    // Validación — texto sin "arrendamiento" retorna error_validacion
    // -------------------------------------------------------------------------

    public function test_texto_sin_arrendamiento_retorna_error_validacion(): void
    {
        $datos = $this->extractor->extraer($this->textoSinArrendamiento());
        $this->assertArrayHasKey('error_validacion', $datos);
    }

    // -------------------------------------------------------------------------
    // validar()
    // -------------------------------------------------------------------------

    public function test_validar_con_error_validacion_es_invalido(): void
    {
        $datos = ['error_validacion' => 'Motivo de error', 'fecha' => '', 'serie' => '', 'numero' => '', 'monto' => 0];
        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
    }

    public function test_validar_datos_completos_es_valido(): void
    {
        $datos = [
            'fecha'  => '10/02/2026',
            'serie'  => 'C',
            'numero' => '99001',
            'monto'  => 8500.0,
        ];
        $resultado = $this->extractor->validar($datos);
        $this->assertTrue($resultado['valid']);
    }

    // -------------------------------------------------------------------------
    // extraerOrdenCobro — vía número único en adenda
    // -------------------------------------------------------------------------

    public function test_orden_cobro_desde_numero_unico_en_adenda(): void
    {
        $textoConNumeroUnico = $this->textoArrendamientoEjemplo();
        $textoConNumeroUnico = str_replace('ORDEN DE COBRO 55678', '55678', $textoConNumeroUnico);
        $datos = $this->extractor->extraer($textoConNumeroUnico);
        $this->assertNotEmpty($datos['orden_cobro']);
    }
}
