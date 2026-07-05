<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\ArmasExtractor;
use App\DTOs\CfeExtraccionDto;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\CfeExtractor\Helpers\WithCfeFixtures;

class ArmasExtractorTest extends TestCase
{
    use WithCfeFixtures;

    private ArmasExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new ArmasExtractor();
    }

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

    public function test_nombre_legible_contiene_arma(): void
    {
        $nombre = $this->extractor->getNombreLegible();
        $this->assertStringContainsStringIgnoringCase('arma', $nombre);
    }

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

    public function test_extrae_porte_desde_fixture_real(): void
    {
        $texto = $this->loadArmasFixture('porte_valido.txt');
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('E', $dto->serie);
        $this->assertSame('77001', $dto->numero);
        $this->assertSame('05/03/2026', $dto->fecha);
        $this->assertSame(450.0, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);
        $this->assertSame('4.321.098-7', $dto->receptorDocumento);
        $this->assertSame('CARLOS RODRIGUEZ SUAREZ', $dto->receptorNombre);
        $this->assertStringContainsString('098765432', $dto->telefono ?? '');
        $this->assertStringContainsString('PORTE', $dto->detalle ?? '');
        $this->assertStringContainsString('12345', $dto->ingresoContabilidad ?? '');
        $this->assertStringContainsString('67890', $dto->ordenCobro ?? '');
    }

    public function test_extrae_tenencia_desde_fixture_real(): void
    {
        $texto = $this->loadArmasFixture('tenencia_valida.txt');
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('E', $dto->serie);
        $this->assertSame('77002', $dto->numero);
        $this->assertSame('10/03/2026', $dto->fecha);
        $this->assertSame(350.0, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);
        $this->assertSame('4.321.098-7', $dto->receptorDocumento);
        $this->assertSame('ANA MARIA FERNANDEZ SILVA', $dto->receptorNombre);
        $this->assertStringContainsString('091234567', $dto->telefono ?? '');
        $this->assertStringContainsString('TENENCIA', $dto->detalle ?? '');
        $this->assertStringContainsString('12346', $dto->ingresoContabilidad ?? '');
        $this->assertStringContainsString('67891', $dto->ordenCobro ?? '');
    }

    public function test_validar_datos_completos(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'E',
            numero: '77001',
            fecha: '05/03/2026',
            monto: 450.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 450.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'porte_armas',
            extractorVersion: '1.0.0',
            telefono: null,
            receptorDocumento: '4.321.098-7',
            receptorNombre: 'CARLOS RODRIGUEZ SUAREZ',
            ingresoContabilidad: '12345',
            ordenCobro: null,
        );
        $this->extractor->validar($dto);
        $this->assertTrue(true);
    }

    public function test_validar_monto_cero_es_invalido(): void
    {
        $this->expectException(\App\Exceptions\CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('Monto no valido');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'E',
            numero: '77001',
            fecha: '05/03/2026',
            monto: 0.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 0.0,
            formaPago: 'Efectivo',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'porte_armas',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }
}
