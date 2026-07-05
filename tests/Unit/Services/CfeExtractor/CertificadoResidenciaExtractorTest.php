<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\CertificadoResidenciaExtractor;
use App\DTOs\CfeExtraccionDto;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\CfeExtractor\Helpers\WithCfeFixtures;

class CertificadoResidenciaExtractorTest extends TestCase
{
    use WithCfeFixtures;

    private CertificadoResidenciaExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new CertificadoResidenciaExtractor();
    }

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
        $this->assertFalse($this->extractor->soporta('certificado'));
    }

    public function test_no_soporta_multas(): void
    {
        $this->assertFalse($this->extractor->soporta('multas_cobradas'));
    }

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Certificado de Residencia', $this->extractor->getNombreLegible());
    }

    public function test_extrae_datos_desde_fixture_real(): void
    {
        $texto = $this->loadCertificadoResidenciaFixture();
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('B', $dto->serie);
        $this->assertSame('654321', $dto->numero);
        $this->assertSame('22/01/2026', $dto->fecha);
        $this->assertSame(300.0, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);
        $this->assertSame('5.678.901-2', $dto->cedulaReceptor);
        $this->assertSame('MARIA GARCIA LOPEZ', $dto->nombreReceptor);
        $this->assertStringContainsString('Efectivo', $dto->formaPago);
        $this->assertStringContainsString('CERTIFICADO DE RESIDENCIA', $dto->detalle ?? '');
    }

    public function test_validar_sin_serie_es_invalido(): void
    {
        $this->expectException(\App\Exceptions\CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('Serie/Numero no detectado');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: '',
            numero: '',
            fecha: '22/01/2026',
            monto: 300.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 300.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'certificado_residencia',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }

    public function test_validar_datos_completos(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'B',
            numero: '654321',
            fecha: '22/01/2026',
            monto: 300.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 300.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'certificado_residencia',
            extractorVersion: '1.0.0',
            telefono: null,
            receptorDocumento: null,
            receptorNombre: null,
            ingresoContabilidad: null,
            ordenCobro: null,
            cedulaReceptor: '5.678.901-2',
            nombreReceptor: 'MARIA GARCIA LOPEZ',
        );
        $this->extractor->validar($dto);
        $this->assertTrue(true);
    }
}
