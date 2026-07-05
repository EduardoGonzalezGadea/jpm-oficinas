<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\PrendasExtractor;
use App\DTOs\CfeExtraccionDto;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\CfeExtractor\Helpers\WithCfeFixtures;

class PrendasExtractorTest extends TestCase
{
    use WithCfeFixtures;

    private PrendasExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new PrendasExtractor();
    }

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

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Prendas', $this->extractor->getNombreLegible());
    }

    public function test_extrae_datos_desde_fixture_real(): void
    {
        $texto = $this->loadPrendasFixture();
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('F', $dto->serie);
        $this->assertSame('33001', $dto->numero);
        $this->assertSame('08/02/2026', $dto->fecha);
        $this->assertSame(600.0, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);
        $this->assertSame('5.678.901-2', $dto->cedula);
        $this->assertSame('CARLOS ANDRES LOPEZ PEREZ', $dto->nombre);
        $this->assertStringContainsString('099123456', $dto->telefono ?? '');
        $this->assertStringContainsString('Efectivo', $dto->formaPago);
        $this->assertStringContainsString('PRENDA', $dto->detalle ?? '');
        $this->assertStringContainsString('SCD 4567', $dto->detalle ?? '');
    }

    public function test_validar_completo(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'F',
            numero: '33001',
            fecha: '08/02/2026',
            monto: 600.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 600.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'prendas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
        $this->assertTrue(true);
    }

    public function test_validar_sin_fecha_es_invalido(): void
    {
        $this->expectException(\App\Exceptions\CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('Fecha no detectada');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'F',
            numero: '33001',
            fecha: '',
            monto: 600.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 600.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'prendas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }
}
