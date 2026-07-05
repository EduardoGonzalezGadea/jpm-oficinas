<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\ArrendamientosExtractor;
use App\DTOs\CfeExtraccionDto;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\CfeExtractor\Helpers\WithCfeFixtures;

class ArrendamientosExtractorTest extends TestCase
{
    use WithCfeFixtures;

    private ArrendamientosExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new ArrendamientosExtractor();
    }

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

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Arrendamientos', $this->extractor->getNombreLegible());
    }

    public function test_extrae_datos_desde_fixture_real(): void
    {
        $texto = $this->loadArrendamientosFixture();
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('C', $dto->serie);
        $this->assertSame('99001', $dto->numero);
        $this->assertSame('10/02/2026', $dto->fecha);
        $this->assertSame(8500.0, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);
        $this->assertSame('3.456.789-0', $dto->cedula);

        $this->assertStringContainsString('MARIA ELENA SOSA GIMENEZ', $dto->nombre ?? '');
        $this->assertStringContainsString('Transferencia Bancaria', $dto->formaPago);
        $this->assertStringContainsString('Local 3', $dto->detalle ?? '');
        $this->assertStringContainsString('18 DE JULIO', $dto->detalle ?? '');
    }

    public function test_validar_datos_completos_es_valido(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'C',
            numero: '99001',
            fecha: '10/02/2026',
            monto: 8500.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 8500.0,
            formaPago: 'Transferencia',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'arrendamientos',
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
            serie: 'C',
            numero: '99001',
            fecha: '',
            monto: 8500.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 8500.0,
            formaPago: 'Transferencia',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'arrendamientos',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }
}
