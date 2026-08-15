<?php

namespace Tests\Feature\Tesoreria\Multas;

use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use Tests\TesoreriaTestCase;

/**
 * Tests de Cobro de Multas
 * 
 * Cubre:
 * - Creación de multas cobradas
 * - Items de multas
 * - Medios de pago
 * - Formas de pago (contado/crédito)
 * - Cálculos de totales
 * - Relaciones entre modelos
 */
class MultasCobradasTest extends TesoreriaTestCase
{
    private MedioDePago $medioEfectivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->medioEfectivo = $this->getMedioDePago('EF');
    }

    public function test_puede_crear_multa_cobrada_basica(): void
    {
        $cobrada = TesMultasCobradas::create([
            'recibo' => 'REC-2026-001',
            'cedula' => '12345678',
            'nombre' => 'Juan Pérez',
            'fecha' => '2026-08-14',
            'monto' => 5000.00,
            'forma_pago' => 'contado',
            'medio_pago_id' => $this->medioEfectivo->id,
        ]);

        $this->assertDatabaseHas('tes_multas_cobradas', [
            'recibo' => 'REC-2026-001',
            'cedula' => '12345678',
        ]);
    }

    public function test_puede_crear_multa_cobrada_con_factory(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        $this->assertNotNull($cobrada->id);
        $this->assertNotNull($cobrada->recibo);
        $this->assertNotNull($cobrada->fecha);
    }

    public function test_multa_cobrada_contado_con_factory(): void
    {
        $cobrada = TesMultasCobradas::factory()->contado()->create();

        $this->assertEquals('contado', $cobrada->forma_pago);
    }

    public function test_multa_cobrada_credito_con_factory(): void
    {
        $cobrada = TesMultasCobradas::factory()->credito()->create();

        $this->assertEquals('credito', $cobrada->forma_pago);
    }

    public function test_multa_cobrada_en_efectivo_con_factory(): void
    {
        $cobrada = TesMultasCobradas::factory()->enEfectivo()->create();

        $this->assertNotNull($cobrada->medio_pago_id);
        $this->assertEquals('EF', $cobrada->medioPago->codigo);
    }

    public function test_puede_agregar_items_a_multa_cobrada(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        $item = TesMultasItems::create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'codigo' => 'M001',
            'descripcion' => 'Multa Art. 103',
            'importe' => 2500.00,
            'subtotal' => 2500.00,
        ]);

        $this->assertEquals(1, $cobrada->items()->count());
        $this->assertEquals('M001', $cobrada->items->first()->codigo);
    }

    public function test_puede_agregar_multiples_items(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        TesMultasItems::factory()->count(3)->create([
            'tes_multas_cobradas_id' => $cobrada->id,
        ]);

        $this->assertEquals(3, $cobrada->items()->count());
    }

    public function test_item_tiene_relacion_con_cobrada(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();
        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
        ]);

        $this->assertEquals($cobrada->id, $item->cobrada->id);
    }

    public function test_multa_cobrada_tiene_medio_pago(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'medio_pago_id' => $this->medioEfectivo->id,
        ]);

        $this->assertNotNull($cobrada->medioPago);
        $this->assertEquals('EF', $cobrada->medioPago->codigo);
    }

    public function test_calculo_de_subtotales_en_items(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'importe' => 1000.00,
            'subtotal' => 1000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'importe' => 2500.00,
            'subtotal' => 2500.00,
        ]);

        $total = $cobrada->items->sum('subtotal');

        $this->assertFloatEquals(3500.00, $total);
    }

    public function test_monto_formateado_accessor(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'monto' => 5234.56,
        ]);

        $formateado = $cobrada->monto_formateado;

        $this->assertStringContainsString('5.234,56', $formateado);
        $this->assertStringContainsString('$', $formateado);
    }

    public function test_multa_con_datos_completos_de_infractor(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'cedula' => '12345678',
            'nombre' => 'Juan Pérez Rodríguez',
            'domicilio' => 'Av. 18 de Julio 1234',
            'adicional' => 'Apto 501',
        ]);

        $this->assertEquals('12345678', $cobrada->cedula);
        $this->assertEquals('Juan Pérez Rodríguez', $cobrada->nombre);
        $this->assertEquals('Av. 18 de Julio 1234', $cobrada->domicilio);
        $this->assertEquals('Apto 501', $cobrada->adicional);
    }

    public function test_multa_con_referencias(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'referencias' => 'Acta Nº 12345 - Fecha: 01/08/2026',
        ]);

        $this->assertStringContainsString('Acta Nº 12345', $cobrada->referencias);
    }

    public function test_multa_con_adenda(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'adenda' => 'Información adicional sobre la infracción',
        ]);

        $this->assertEquals('Información adicional sobre la infracción', $cobrada->adenda);
    }

    public function test_items_con_monto_ur(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'codigo' => 'M001',
            'descripcion' => 'Multa en UR',
            'monto_ur' => 50.5000,
            'monto_pesos' => 0,
            'subtotal' => 5000.00, // 50.5 UR * valor UR
        ]);

        $this->assertFloatEquals(50.5000, $item->monto_ur);
        $this->assertFloatEquals(5000.00, $item->subtotal);
    }

    public function test_items_con_monto_pesos(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'codigo' => 'M002',
            'descripcion' => 'Multa en Pesos',
            'monto_pesos' => 3500.00,
            'monto_ur' => 0,
            'subtotal' => 3500.00,
        ]);

        $this->assertFloatEquals(3500.00, $item->monto_pesos);
        $this->assertFloatEquals(3500.00, $item->subtotal);
    }

    public function test_item_con_detalle_adicional(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'descripcion' => 'Multa Art. 103',
            'detalle' => 'Exceso de velocidad en zona escolar - 80 km/h en zona de 45 km/h',
        ]);

        $this->assertStringContainsString('Exceso de velocidad', $item->detalle);
    }

    public function test_puede_actualizar_multa_cobrada(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'monto' => 5000.00,
        ]);

        $cobrada->update(['monto' => 6000.00]);

        $this->assertFloatEquals(6000.00, $cobrada->fresh()->monto);
    }

    public function test_puede_actualizar_item(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();
        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'subtotal' => 1000.00,
        ]);

        $item->update(['subtotal' => 1500.00]);

        $this->assertFloatEquals(1500.00, $item->fresh()->subtotal);
    }

    public function test_puede_eliminar_multa_cobrada_soft_delete(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();
        $id = $cobrada->id;

        $cobrada->delete();

        $this->assertSoftDeleted('tes_multas_cobradas', ['id' => $id]);
    }

    public function test_puede_eliminar_item_soft_delete(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();
        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
        ]);
        $itemId = $item->id;

        $item->delete();

        $this->assertSoftDeleted('tes_multas_items', ['id' => $itemId]);
    }

    public function test_multa_cobrada_tiene_timestamps(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();

        $this->assertNotNull($cobrada->created_at);
        $this->assertNotNull($cobrada->updated_at);
    }

    public function test_item_tiene_timestamps(): void
    {
        $cobrada = TesMultasCobradas::factory()->create();
        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
        ]);

        $this->assertNotNull($item->created_at);
        $this->assertNotNull($item->updated_at);
    }

    public function test_fecha_se_guarda_como_datetime(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'fecha' => '2026-08-14 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cobrada->fecha);
        $this->assertEquals('2026-08-14 10:30:00', $cobrada->fecha->format('Y-m-d H:i:s'));
    }

    public function test_recibo_unico_por_multa(): void
    {
        $recibo = 'REC-2026-' . str_pad(1, 6, '0', STR_PAD_LEFT);

        $cobrada = TesMultasCobradas::factory()->create([
            'recibo' => $recibo,
        ]);

        $this->assertEquals($recibo, $cobrada->recibo);
    }

    public function test_multa_con_valores_decimales_precisos(): void
    {
        $cobrada = TesMultasCobradas::factory()->create([
            'monto' => 1234.56,
        ]);

        $item = TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $cobrada->id,
            'importe' => 617.28,
            'subtotal' => 617.28,
        ]);

        $this->assertFloatEquals(1234.56, $cobrada->monto, 0.01);
        $this->assertFloatEquals(617.28, $item->importe, 0.01);
    }
}
