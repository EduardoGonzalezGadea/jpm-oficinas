<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Services\CfeExtractor\EventualesExtractor;
use App\DTOs\CfeExtraccionDto;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Services\CfeExtractor\Helpers\WithCfeFixtures;

class EventualesExtractorTest extends TestCase
{
    use WithCfeFixtures;

    private EventualesExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new EventualesExtractor();
    }

    public function test_soporta_eventuales(): void
    {
        $this->assertTrue($this->extractor->soporta('eventuales'));
    }

    public function test_soporta_policias_eventuales(): void
    {
        $this->assertTrue($this->extractor->soporta('policias_eventuales'));
    }

    public function test_soporta_aguinaldo(): void
    {
        $this->assertTrue($this->extractor->soporta('aguinaldo'));
    }

    public function test_soporta_generico(): void
    {
        $this->assertTrue($this->extractor->soporta('generico'));
    }

    public function test_soporta_efactura(): void
    {
        $this->assertTrue($this->extractor->soporta('e-factura'));
    }

    public function test_no_soporta_arrendamientos(): void
    {
        $this->assertFalse($this->extractor->soporta('arrendamientos'));
    }

    public function test_no_soporta_prendas(): void
    {
        $this->assertFalse($this->extractor->soporta('prendas'));
    }

    public function test_no_soporta_multas(): void
    {
        $this->assertFalse($this->extractor->soporta('multas_cobradas'));
    }

    public function test_nombre_legible_es_string_no_vacio(): void
    {
        $nombre = $this->extractor->getNombreLegible();
        $this->assertIsString($nombre);
        $this->assertNotEmpty($nombre);
    }

    public function test_nombre_legible_contiene_eventuales(): void
    {
        $nombre = $this->extractor->getNombreLegible();
        $this->assertStringContainsStringIgnoringCase('Eventuales', $nombre);
    }

    public function test_extrae_datos_desde_fixture_real(): void
    {
        $texto = $this->loadEventualesFixture();
        $dto = $this->extractor->extraer($texto);

        $this->assertSame('A', $dto->serie);
        $this->assertSame('4873', $dto->numero);
        $this->assertSame('22/05/2026', $dto->fecha);
        $this->assertSame(340222.80, $dto->monto);
        $this->assertSame('UYU', $dto->moneda);

        $this->assertStringContainsString('INTENDENCIA DE MONTEVIDEO', $dto->receptorNombre ?? '');
        $this->assertStringContainsString('RECURSOS FINANCIEROS', $dto->receptorNombre ?? '');

        $this->assertCount(2, $dto->items);

        $primerItem = $dto->items[0];
        $this->assertSame('Nocturnidad', $primerItem['concepto']);
        $this->assertSame(13774.32, $primerItem['importe']);

        $segundoItem = $dto->items[1];
        $this->assertSame('Sueldos', $segundoItem['concepto']);
        $this->assertSame(326448.48, $segundoItem['importe']);

        $this->assertStringContainsString('e-Factura-A-3679', $dto->referencias ?? '');
        $this->assertStringContainsString('ING. 3020', $dto->adenda ?? '');
    }

    public function test_validar_datos_completos(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'A',
            numero: '123456',
            fecha: '15/03/2026',
            monto: 1500.0,
            moneda: 'UYU',
            cedula: '1.234.567-8',
            nombre: 'TEST RECEPTOR',
            domicilio: null,
            montoTotal: 1500.0,
            formaPago: 'Transferencia',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [['importe' => 1500.0]],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'eventuales',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
        $this->assertTrue(true);
    }

    public function test_validar_sin_monto_es_invalido(): void
    {
        $this->expectException(\App\Exceptions\CfeExtraccionInvalidaException::class);
        $this->expectExceptionMessage('Monto no valido');

        $dto = new CfeExtraccionDto(
            tipoCfe: 'test',
            serie: 'A',
            numero: '123456',
            fecha: '15/03/2026',
            monto: 0.0,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: 0.0,
            formaPago: 'Transferencia',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalle: null,
            detalleCompleto: null,
            tipoCfeCodigo: 'eventuales',
            extractorVersion: '1.0.0',
        );
        $this->extractor->validar($dto);
    }
}
