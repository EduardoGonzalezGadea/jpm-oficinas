<?php

namespace Tests\Unit\Services\Tesoreria;

use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use App\Services\Tesoreria\MultasNormalizationService;
use Tests\TesoreriaTestCase;

/**
 * Tests de MultasNormalizationService.
 *
 * Cubre:
 * - Clasificación de ítems con formato "ART. X" (artículo + apartado)
 * - Clasificación de ítems con nuevo formato "COD. X.Y.Z" (D.677/007)
 * - Agrupación de ítems con el mismo código y precio
 * - Preservación del caso especial SOA (artículo 184)
 * - Ítems sin patrón reconocido quedan como "Otros / Sin Clasificar"
 */
class MultasNormalizationServiceTest extends TesoreriaTestCase
{
    private MultasNormalizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MultasNormalizationService();
    }

    public function test_clasifica_item_con_formato_art_como_articulo_y_apartado(): void
    {
        $this->crearItem('MULTAS DE TRANSITO- ART.8 APART.1 CONDUCE SIN LICENCIA O SUSPENDIDA.', 28673);

        $data = $this->service->getResumenData('2026-01-13', '2026-08-19');

        $grupo = $data['grouped']->first(fn ($g) => $g->articulo === '8');

        $this->assertNotNull($grupo);
        $this->assertEquals('Ap. 1', $grupo->apartado);
        $this->assertEquals(1, $grupo->cantidad);
        $this->assertCount(0, $data['unclassified']);
    }

    public function test_clasifica_codigo_21_3_1_como_cod_21_apartado_3_1(): void
    {
        $this->crearItem('MULTA DE TRÁNSITO - COD. 21.3.1 CIRCULAR SIN CASCO EN MOTO O BICICLETA', 1921);

        $data = $this->service->getResumenData('2026-01-13', '2026-08-19');

        $grupo = $data['grouped']->first(fn ($g) => $g->articulo === 'COD. 21' && $g->apartado === '3.1');

        $this->assertNotNull($grupo);
        $this->assertEquals(1, $grupo->cantidad);
        $this->assertCount(0, $data['unclassified']);
    }

    public function test_clasifica_codigo_de_dos_segmentos(): void
    {
        $this->crearItem('MULTA DE TRÁNSITO - COD. 12.5 NO MANTENER LA DERECHA AL CRUZARSE', 7685);

        $data = $this->service->getResumenData('2026-01-13', '2026-08-19');

        $grupo = $data['grouped']->first(fn ($g) => $g->articulo === 'COD. 12' && $g->apartado === '5');

        $this->assertNotNull($grupo);
        $this->assertEquals(1, $grupo->cantidad);
        $this->assertCount(0, $data['unclassified']);
    }

    public function test_codigo_soa_mantiene_clasificacion_como_articulo_184(): void
    {
        $this->crearItem('MULTA DE TRÁNSITO - COD. 27.1 CIRCULAR SIN SEGURO OBLIGATORIO SOA VIGENTE', 28673);

        $data = $this->service->getResumenData('2026-01-13', '2026-08-19');

        $grupo = $data['grouped']->first(fn ($g) => $g->articulo === '184');

        $this->assertNotNull($grupo);
        $this->assertCount(0, $data['unclassified']);
    }

    public function test_agrupa_items_con_mismo_codigo_y_importe(): void
    {
        $this->crearItem('MULTA DE TRÁNSITO - COD. 21.3.1 CIRCULAR SIN CASCO EN MOTO O BICICLETA', 1921);
        $this->crearItem('MULTA DE TRÁNSITO - COD. 21.3.1 CIRCULAR SIN CASCO EN MOTO O BICICLETA', 1921);

        $data = $this->service->getResumenData('2026-01-13', '2026-08-19');

        $grupo = $data['grouped']->first(fn ($g) => $g->articulo === 'COD. 21' && $g->apartado === '3.1');

        $this->assertNotNull($grupo);
        $this->assertEquals(2, $grupo->cantidad);
        $this->assertEquals(3842, (int) $grupo->importe_total);
        $this->assertCount(0, $data['unclassified']);
    }

    public function test_item_sin_patron_queda_en_no_clasificados(): void
    {
        $this->crearItem('CERTIFICADO DE RESIDENCIA', 1000);

        $data = $this->service->getResumenData('2026-01-13', '2026-08-19');

        $this->assertCount(0, $data['grouped']);
        $this->assertCount(1, $data['unclassified']);
        $this->assertEquals('CERTIFICADO DE RESIDENCIA', $data['unclassified']->first()->detalle);
    }

    private function crearItem(string $detalle, float $importe): void
    {
        $cobrada = TesMultasCobradas::create([
            'recibo' => 'A-' . strtoupper(uniqid()),
            'cedula' => '12345678',
            'nombre' => 'Persona de Prueba',
            'fecha' => '2026-03-04',
            'monto' => $importe,
            'forma_pago' => 'Efectivo: 1.921,00',
        ]);

        TesMultasItems::create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'detalle' => $detalle,
            'descripcion' => '',
            'importe' => $importe,
        ]);
    }
}