<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\MultasExtractor;
use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\CfeExtractor\Helpers\WithCfeFixtures;

class MultasExtractorTest extends TestCase
{
    use WithCfeFixtures;

    private MultasExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new MultasExtractor();
    }

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

    public function test_nombre_legible(): void
    {
        $this->assertEquals('Multas Cobradas', $this->extractor->getNombreLegible());
    }

    public function test_extrae_datos_desde_fixture_real(): void
    {
        $texto = $this->loadMultasFixture();
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('A', $dto->serie);
        $this->assertSame('4788', $dto->numero);
        $this->assertSame('15/03/2026', $dto->fecha);
        $this->assertSame(1500.0, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);
        $this->assertSame('1.234.567-8', $dto->cedula);
        $this->assertSame('JUAN CARLOS PEREZ RODRIGUEZ', $dto->nombre);
        $this->assertStringContainsString('Efectivo', $dto->formaPago);
        $this->assertCount(1, $dto->items);

        $item = $dto->items[0];
        $this->assertSame('MULTA CARECER DE SOA', $item['detalle']);
        $this->assertStringContainsString('CORRESPONDE A', $item['descripcion']);
        $this->assertSame(1500.0, $item['importe']);

        $this->assertStringContainsString('Recibo A-4787', $dto->referencias ?? '');
        $this->assertStringContainsString('ING. 3020', $dto->adenda ?? '');
    }

    public function test_extraccion_lanza_excepcion_con_texto_vacio(): void
    {
        $this->expectException(CfeExtraccionInvalidaException::class);
        $this->extractor->extraer('');
    }

    public function test_validar_datos_completos_es_valido(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'A',
            numero: '123456',
            fecha: '15/03/2026',
            monto: 1500.0,
            moneda: 'UYU',
            cedula: '1.234.567-8',
            nombre: 'JUAN PEREZ RODRIGUEZ',
            domicilio: null,
            montoTotal: 1500.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [['detalle' => 'MULTA CARECER DE SOA', 'descripcion' => '', 'importe' => 1500.0]],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'multas_cobradas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
        $this->assertTrue(true);
    }

    public function test_validar_sin_fecha_es_invalido(): void
    {
        $this->expectException(CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('Fecha no detectada');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'A',
            numero: '123456',
            fecha: '',
            monto: 1500.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 1500.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [['detalle' => 'MULTA', 'descripcion' => '', 'importe' => 1500.0]],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'multas_cobradas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }

    public function test_validar_sin_items_es_invalido(): void
    {
        $this->expectException(CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('No se detectaron items en el CFE');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'A',
            numero: '123456',
            fecha: '15/03/2026',
            monto: 1500.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 1500.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'multas_cobradas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }

    public function test_validar_inconsistencia_monto_items_es_invalido(): void
    {
        $this->expectException(CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('Inconsistencia');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'A',
            numero: '123456',
            fecha: '15/03/2026',
            monto: 2000.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 2000.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [['detalle' => 'MULTA', 'descripcion' => '', 'importe' => 1500.0]],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'multas_cobradas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }
}
