<?php

namespace Tests\Feature\Tesoreria\CFE;

use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfeMedioPago;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\MedioDePago;
use Tests\TesoreriaTestCase;

/**
 * Tests Básicos de CFE (Comprobante Fiscal Electrónico)
 * 
 * Cubre:
 * - Creación de CFE
 * - Items de CFE
 * - Medios de pago
 * - Relaciones entre modelos
 * - Campos básicos
 */
class CfeBasicTest extends TesoreriaTestCase
{
    public function test_puede_crear_cfe_basico(): void
    {
        $cfe = TesCfe::create([
            'documento_tipo' => 'eFactura',
            'documento_serie' => 'A',
            'documento_numero' => '123456',
            'fecha' => '2026-08-14',
            'receptor_nombre_denominacion' => 'Juan Pérez',
            'total_a_pagar' => 5000.00,
        ]);

        $this->assertDatabaseHas('tes_cfes', [
            'documento_tipo' => 'eFactura',
            'documento_numero' => '123456',
        ]);
    }

    public function test_cfe_tiene_items(): void
    {
        $cfe = TesCfe::factory()->create();

        $item = TesCfeItem::create([
            'tes_cfe_id' => $cfe->id,
            'descripcion' => 'Item de prueba',
            'cantidad' => 1,
            'precio_unitario' => 1000.00,
            'subtotal' => 1000.00,
        ]);

        $this->assertEquals(1, $cfe->items()->count());
        $this->assertEquals('Item de prueba', $cfe->items->first()->descripcion);
    }

    public function test_cfe_tiene_medios_pago(): void
    {
        $cfe = TesCfe::factory()->create();
        $medio = $this->getMedioDePago('EF');

        $cfeMedio = TesCfeMedioPago::create([
            'tes_cfe_id' => $cfe->id,
            'medio_pago_id' => $medio->id,
            'monto' => 2500.00,
        ]);

        $this->assertEquals(1, $cfe->mediosPago()->count());
    }

    public function test_puede_agregar_multiples_items(): void
    {
        $cfe = TesCfe::factory()->create();

        TesCfeItem::factory()->count(3)->create([
            'tes_cfe_id' => $cfe->id,
        ]);

        $this->assertEquals(3, $cfe->items()->count());
    }

    public function test_puede_agregar_multiples_medios_pago(): void
    {
        $cfe = TesCfe::factory()->create();
        $medioEfectivo = $this->getMedioDePago('EF');
        $medioCheque = $this->getMedioDePago('CH');

        TesCfeMedioPago::create([
            'tes_cfe_id' => $cfe->id,
            'medio_pago_id' => $medioEfectivo->id,
            'monto' => 2500.00,
        ]);

        TesCfeMedioPago::create([
            'tes_cfe_id' => $cfe->id,
            'medio_pago_id' => $medioCheque->id,
            'monto' => 2500.00,
        ]);

        $this->assertEquals(2, $cfe->mediosPago()->count());
    }

    public function test_cfe_tiene_campos_receptor(): void
    {
        $cfe = TesCfe::factory()->create([
            'receptor_nombre_denominacion' => 'Juan Pérez',
            'receptor_ruc' => '211234560018',
            'receptor_domicilio' => 'Av. 18 de Julio 1234',
        ]);

        $this->assertEquals('Juan Pérez', $cfe->receptor_nombre_denominacion);
        $this->assertEquals('211234560018', $cfe->receptor_ruc);
        $this->assertEquals('Av. 18 de Julio 1234', $cfe->receptor_domicilio);
    }

    public function test_cfe_tiene_campos_monetarios_correctos(): void
    {
        $cfe = TesCfe::factory()->create([
            'monto_no_facturable' => 100.50,
            'monto_total' => 5100.50,
            'total_a_pagar' => 5000.00,
        ]);

        $this->assertFloatEquals(100.50, $cfe->monto_no_facturable);
        $this->assertFloatEquals(5100.50, $cfe->monto_total);
        $this->assertFloatEquals(5000.00, $cfe->total_a_pagar);
    }

    public function test_cfe_tiene_fecha_y_vencimiento(): void
    {
        $cfe = TesCfe::factory()->create([
            'fecha' => '2026-08-01',
            'vencimiento' => '2026-08-31',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cfe->fecha);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cfe->vencimiento);
        $this->assertEquals('2026-08-01', $cfe->fecha->format('Y-m-d'));
        $this->assertEquals('2026-08-31', $cfe->vencimiento->format('Y-m-d'));
    }

    public function test_cfe_puede_tener_referencias_y_adenda(): void
    {
        $cfe = TesCfe::factory()->create([
            'referencias' => 'Referencia a documento anterior',
            'adenda' => 'Información adicional del CFE',
        ]);

        $this->assertEquals('Referencia a documento anterior', $cfe->referencias);
        $this->assertEquals('Información adicional del CFE', $cfe->adenda);
    }

    public function test_cfe_tiene_relacion_con_caja_concepto(): void
    {
        // Crear concepto de caja
        $concepto = CajaConcepto::factory()->create([
            'nombre' => 'Multas de Tránsito',
        ]);

        $cfe = TesCfe::factory()->create([
            'tes_caja_concepto_id' => $concepto->id,
        ]);

        $this->assertNotNull($cfe->cajaConcepto);
        $this->assertEquals('Multas de Tránsito', $cfe->cajaConcepto->nombre);
    }

    public function test_cfe_tiene_soft_delete(): void
    {
        $cfe = TesCfe::factory()->create();
        $id = $cfe->id;

        $cfe->delete();

        $this->assertSoftDeleted('tes_cfes', ['id' => $id]);
        $this->assertNotNull($cfe->fresh()->deleted_at);
    }

    public function test_cfe_tiene_timestamps(): void
    {
        $cfe = TesCfe::factory()->create();

        $this->assertNotNull($cfe->created_at);
        $this->assertNotNull($cfe->updated_at);
    }

    public function test_item_tiene_campos_basicos(): void
    {
        $cfe = TesCfe::factory()->create();

        $item = TesCfeItem::create([
            'tes_cfe_id' => $cfe->id,
            'descripcion' => 'Servicio de consultoría',
            'cantidad' => 2,
            'precio_unitario' => 1500.00,
            'subtotal' => 3000.00,
        ]);

        $this->assertEquals('Servicio de consultoría', $item->descripcion);
        $this->assertEquals(2, $item->cantidad);
        $this->assertFloatEquals(1500.00, $item->precio_unitario);
        $this->assertFloatEquals(3000.00, $item->subtotal);
    }

    public function test_calculo_de_subtotales_items(): void
    {
        $cfe = TesCfe::factory()->create();

        TesCfeItem::create([
            'tes_cfe_id' => $cfe->id,
            'descripcion' => 'Item 1',
            'cantidad' => 1,
            'precio_unitario' => 1000.00,
            'subtotal' => 1000.00,
        ]);

        TesCfeItem::create([
            'tes_cfe_id' => $cfe->id,
            'descripcion' => 'Item 2',
            'cantidad' => 2,
            'precio_unitario' => 750.00,
            'subtotal' => 1500.00,
        ]);

        $total = $cfe->items->sum('subtotal');

        $this->assertFloatEquals(2500.00, $total);
    }

    public function test_medio_pago_tiene_monto(): void
    {
        $cfe = TesCfe::factory()->create();
        $medio = $this->getMedioDePago('EF');

        $cfeMedio = TesCfeMedioPago::create([
            'tes_cfe_id' => $cfe->id,
            'medio_pago_id' => $medio->id,
            'monto' => 3500.00,
        ]);

        $this->assertFloatEquals(3500.00, $cfeMedio->monto);
    }

    public function test_cfe_con_diferentes_tipos_documento(): void
    {
        $eFactura = TesCfe::factory()->create(['documento_tipo' => 'eFactura']);
        $eTicket = TesCfe::factory()->create(['documento_tipo' => 'eTicket']);
        $eRemito = TesCfe::factory()->create(['documento_tipo' => 'eRemito']);

        $this->assertEquals('eFactura', $eFactura->documento_tipo);
        $this->assertEquals('eTicket', $eTicket->documento_tipo);
        $this->assertEquals('eRemito', $eRemito->documento_tipo);
    }

    public function test_puede_actualizar_cfe(): void
    {
        $cfe = TesCfe::factory()->create([
            'total_a_pagar' => 5000.00,
        ]);

        $cfe->update(['total_a_pagar' => 6000.00]);

        $this->assertFloatEquals(6000.00, $cfe->fresh()->total_a_pagar);
    }

    public function test_puede_eliminar_item(): void
    {
        $cfe = TesCfe::factory()->create();
        $item = TesCfeItem::factory()->create([
            'tes_cfe_id' => $cfe->id,
        ]);

        $itemId = $item->id;
        $item->delete();

        $this->assertSoftDeleted('tes_cfe_items', ['id' => $itemId]);
    }

    public function test_cfe_con_valores_decimales_precisos(): void
    {
        $cfe = TesCfe::factory()->create([
            'monto_total' => 1234.56,
            'total_a_pagar' => 1234.56,
        ]);

        $this->assertFloatEquals(1234.56, $cfe->monto_total, 0.01);
        $this->assertFloatEquals(1234.56, $cfe->total_a_pagar, 0.01);
    }
}
