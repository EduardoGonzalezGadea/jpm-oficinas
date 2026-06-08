<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\CertificadoResidenciaExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para CertificadoResidenciaExtractor.
 */
class CertificadoResidenciaExtractorTest extends TestCase
{
    private CertificadoResidenciaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new CertificadoResidenciaExtractor();
    }

    // -------------------------------------------------------------------------
    // soporta()
    // -------------------------------------------------------------------------

    public function test_soporta_certificado_residencia(): void
    {
        $this->assertTrue($this->extractor->soporta('certificado_residencia'));
    }

    public function test_soporta_tipo_con_palabras_clave(): void
    {
        $this->assertTrue($this->extractor->soporta('certificado de residencia'));
    }

    public function test_no_soporta_certificado_sin_residencia(): void
    {
        // "certificado" solo no alcanza
        $this->assertFalse($this->extractor->soporta('certificado'));
    }

    public function test_no_soporta_multas(): void
    {
        $this->assertFalse($this->extractor->soporta('multas_cobradas'));
    }

    // -------------------------------------------------------------------------
    // getNombreLegible()
    // -------------------------------------------------------------------------

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Certificado de Residencia', $this->extractor->getNombreLegible());
    }

    // -------------------------------------------------------------------------
    // extraer() — texto sintético con estructura de CFE
    // -------------------------------------------------------------------------

    private function textoCertificadoEjemplo(): string
    {
        return <<<'TEXT'
e-Ticket Contado
B 654321 Contado
NOMBRE O DENOMINACIÓN DOMICILIO FISCAL
MARIA GARCIA LOPEZ
INFORMACION ADICIONAL
C.I.: 5.678.901-2
TEL.: 099 123 456
FECHA MONEDA
22/01/2026 Peso uruguayo

DETALLE DESCRIPCIÓN CANTIDAD PRECIO UNITARIO IMPORTE
CERTIFICADO DE RESIDENCIA CORRESPONDE A TRAMITE 1 300,00 300,00

MONTO NO FACTURABLE: 300,00
TOTAL A PAGAR: 300,00
Efectivo: 300,00
REFERENCIAS:
TEXT;
    }

    public function test_extrae_fecha(): void
    {
        $datos = $this->extractor->extraer($this->textoCertificadoEjemplo());
        $this->assertEquals('22/01/2026', $datos['fecha']);
    }

    public function test_extrae_serie_y_numero(): void
    {
        $datos = $this->extractor->extraer($this->textoCertificadoEjemplo());
        $this->assertEquals('B', $datos['serie']);
        $this->assertEquals('654321', $datos['numero']);
    }

    public function test_extrae_cedula_receptor(): void
    {
        $datos = $this->extractor->extraer($this->textoCertificadoEjemplo());
        $this->assertEquals('5.678.901-2', $datos['cedula_receptor']);
    }

    public function test_extrae_moneda(): void
    {
        $datos = $this->extractor->extraer($this->textoCertificadoEjemplo());
        $this->assertEquals('UYU', $datos['moneda']);
    }

    public function test_extrae_monto(): void
    {
        $datos = $this->extractor->extraer($this->textoCertificadoEjemplo());
        $this->assertEquals(300.0, $datos['monto']);
    }

    // -------------------------------------------------------------------------
    // retira_es_titular — lógica de cedula_titular
    // -------------------------------------------------------------------------

    public function test_cuando_no_hay_ci_en_descripcion_retira_es_titular(): void
    {
        // Sin CI en descripción → el receptor es el titular
        $datos = $this->extractor->extraer($this->textoCertificadoEjemplo());
        $this->assertTrue($datos['retira_es_titular']);
        $this->assertEquals($datos['cedula_receptor'], $datos['cedula_titular']);
    }

    public function test_validar_sin_serie_es_invalido(): void
    {
        $datos = [
            'fecha'      => '22/01/2026',
            'serie'      => '',
            'numero'     => '',
            'monto'      => 300.0,
        ];

        $resultado = $this->extractor->validar($datos);
        $this->assertFalse($resultado['valid']);
        $this->assertContains('Serie/Numero no detectado', $resultado['errors']);
    }

    public function test_validar_datos_completos(): void
    {
        $datos = [
            'fecha'  => '22/01/2026',
            'serie'  => 'B',
            'numero' => '654321',
            'monto'  => 300.0,
        ];

        $resultado = $this->extractor->validar($datos);
        $this->assertTrue($resultado['valid']);
    }
}
