<?php

namespace Tests\Feature\Tesoreria\CFE;

use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfeMedioPago;
use Tests\TesoreriaTestCase;

/**
 * Tests de Flujo de Trabajo de CFE
 * 
 * Cubre:
 * - Estados de CFE (pendiente, confirmado, rechazado)
 * - Transiciones de estado
 * - Validaciones
 * - Flujo completo
 */
class CfeWorkflowTest extends TesoreriaTestCase
{
    public function test_cfe_nuevo_esta_pendiente(): void
    {
        $cfe = TesCfe::factory()->create();

        $this->assertEquals('pendiente', $cfe->status);
    }

    public function test_puede_crear_cfe_pendiente_con_factory(): void
    {
        $cfe = TesCfe::factory()->pendiente()->create();

        $this->assertEquals('pendiente', $cfe->status);
    }

    public function test_puede_crear_cfe_confirmado_con_factory(): void
    {
        $cfe = TesCfe::factory()->confirmado()->create();

        $this->assertEquals('confirmado', $cfe->status);
    }

    public function test_cfe_puede_tener_hash_pdf(): void
    {
        $hash = hash('sha256', 'contenido_pdf_test');

        $cfe = TesCfe::factory()->create([
            'pdf_hash' => $hash,
        ]);

        $this->assertEquals($hash, $cfe->pdf_hash);
    }

    public function test_cfe_puede_tener_nombre_archivo_pdf(): void
    {
        $cfe = TesCfe::factory()->conPdf()->create();

        $this->assertNotNull($cfe->pdf_file_name);
        $this->assertStringContainsString('.pdf', $cfe->pdf_file_name);
    }

    public function test_flujo_completo_cfe_con_items_y_medios_pago(): void
    {
        // 1. Crear CFE pendiente
        $cfe = TesCfe::factory()->pendiente()->create([
            'total_a_pagar' => 5000.00,
        ]);

        // 2. Agregar items
        TesCfeItem::factory()->count(2)->paraCfe($cfe)->create();

        // 3. Agregar medios de pago
        $medioEfectivo = $this->getMedioDePago('EF');
        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioEfectivo)
            ->conMonto(5000.00)
            ->create();

        // 4. Confirmar CFE
        $cfe->update(['status' => 'confirmado']);

        // Verificaciones
        $this->assertEquals('confirmado', $cfe->fresh()->status);
        $this->assertEquals(2, $cfe->items()->count());
        $this->assertEquals(1, $cfe->mediosPago()->count());
        $this->assertFloatEquals(5000.00, $cfe->mediosPago->first()->monto);
    }

    public function test_puede_cambiar_estado_de_pendiente_a_confirmado(): void
    {
        $cfe = TesCfe::factory()->pendiente()->create();

        $cfe->update(['status' => 'confirmado']);

        $this->assertEquals('confirmado', $cfe->fresh()->status);
    }

    public function test_puede_cambiar_estado_de_pendiente_a_rechazado(): void
    {
        $cfe = TesCfe::factory()->pendiente()->create();

        $cfe->update(['status' => 'rechazado']);

        $this->assertEquals('rechazado', $cfe->fresh()->status);
    }

    public function test_cfe_con_concepto_asignado(): void
    {
        $cfe = TesCfe::factory()
            ->pendiente()
            ->conConcepto()
            ->create();

        $this->assertNotNull($cfe->tes_caja_concepto_id);
        $this->assertNotNull($cfe->cajaConcepto);
    }

    public function test_multiples_cfes_diferentes_estados(): void
    {
        TesCfe::factory()->count(3)->pendiente()->create();
        TesCfe::factory()->count(2)->confirmado()->create();

        $pendientes = TesCfe::where('status', 'pendiente')->get();
        $confirmados = TesCfe::where('status', 'confirmado')->get();

        $this->assertCount(3, $pendientes);
        $this->assertCount(2, $confirmados);
    }

    public function test_cfe_con_diferentes_tipos_documento_workflow(): void
    {
        $eFactura = TesCfe::factory()->eFactura()->pendiente()->create();
        $eTicket = TesCfe::factory()->eTicket()->pendiente()->create();

        $this->assertEquals('eFactura', $eFactura->documento_tipo);
        $this->assertEquals('eTicket', $eTicket->documento_tipo);
        $this->assertEquals('pendiente', $eFactura->status);
        $this->assertEquals('pendiente', $eTicket->status);
    }

    public function test_cfe_mantiene_integridad_items_al_confirmar(): void
    {
        $cfe = TesCfe::factory()->pendiente()->create();

        // Agregar items
        $item1 = TesCfeItem::factory()->paraCfe($cfe)->create(['subtotal' => 2000.00]);
        $item2 = TesCfeItem::factory()->paraCfe($cfe)->create(['subtotal' => 3000.00]);

        // Confirmar
        $cfe->update(['status' => 'confirmado']);

        // Verificar que items siguen ahí
        $cfe->refresh();
        $this->assertEquals(2, $cfe->items()->count());
        $this->assertFloatEquals(5000.00, $cfe->items->sum('subtotal'));
    }

    public function test_cfe_mantiene_integridad_medios_pago_al_confirmar(): void
    {
        $cfe = TesCfe::factory()->pendiente()->create();

        // Agregar medios de pago
        $medioEfectivo = $this->getMedioDePago('EF');
        $medioCheque = $this->getMedioDePago('CH');

        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioEfectivo)
            ->conMonto(3000.00)
            ->create();

        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioCheque)
            ->conMonto(2000.00)
            ->create();

        // Confirmar
        $cfe->update(['status' => 'confirmado']);

        // Verificar que medios siguen ahí
        $cfe->refresh();
        $this->assertEquals(2, $cfe->mediosPago()->count());
        $this->assertFloatEquals(5000.00, $cfe->mediosPago->sum('monto'));
    }

    public function test_buscar_cfes_por_estado(): void
    {
        TesCfe::factory()->count(5)->pendiente()->create();
        TesCfe::factory()->count(3)->confirmado()->create();

        $pendientes = TesCfe::where('status', 'pendiente')->count();
        $confirmados = TesCfe::where('status', 'confirmado')->count();

        $this->assertEquals(5, $pendientes);
        $this->assertEquals(3, $confirmados);
    }

    public function test_buscar_cfes_por_tipo_documento(): void
    {
        TesCfe::factory()->count(4)->eFactura()->create();
        TesCfe::factory()->count(2)->eTicket()->create();

        $eFacturas = TesCfe::where('documento_tipo', 'eFactura')->count();
        $eTickets = TesCfe::where('documento_tipo', 'eTicket')->count();

        $this->assertEquals(4, $eFacturas);
        $this->assertEquals(2, $eTickets);
    }

    public function test_buscar_cfes_por_rango_fechas(): void
    {
        TesCfe::factory()->create(['fecha' => '2026-08-01']);
        TesCfe::factory()->create(['fecha' => '2026-08-10']);
        TesCfe::factory()->create(['fecha' => '2026-08-20']);

        $cfesRango = TesCfe::whereBetween('fecha', ['2026-08-05', '2026-08-15'])->count();

        $this->assertEquals(1, $cfesRango);
    }

    public function test_buscar_cfes_por_monto_minimo(): void
    {
        TesCfe::factory()->conMonto(1000.00)->create();
        TesCfe::factory()->conMonto(5000.00)->create();
        TesCfe::factory()->conMonto(10000.00)->create();

        $cfesCaros = TesCfe::where('total_a_pagar', '>=', 5000)->count();

        $this->assertEquals(2, $cfesCaros);
    }

    public function test_filtrar_cfes_sin_eliminar(): void
    {
        TesCfe::factory()->count(3)->create();
        $eliminado = TesCfe::factory()->create();
        $eliminado->delete();

        $activos = TesCfe::all()->count();

        $this->assertEquals(3, $activos);
    }

    public function test_buscar_cfes_incluyendo_eliminados(): void
    {
        TesCfe::factory()->count(3)->create();
        $eliminado = TesCfe::factory()->create();
        $eliminado->delete();

        $todos = TesCfe::withTrashed()->count();

        $this->assertEquals(4, $todos);
    }

    public function test_cfe_con_pdf_puede_confirmar(): void
    {
        $cfe = TesCfe::factory()
            ->pendiente()
            ->conPdf()
            ->create();

        $this->assertNotNull($cfe->pdf_hash);
        $this->assertEquals('pendiente', $cfe->status);

        $cfe->update(['status' => 'confirmado']);

        $this->assertEquals('confirmado', $cfe->fresh()->status);
    }

    public function test_ordenar_cfes_por_fecha(): void
    {
        TesCfe::factory()->create(['fecha' => '2026-08-15']);
        TesCfe::factory()->create(['fecha' => '2026-08-10']);
        TesCfe::factory()->create(['fecha' => '2026-08-20']);

        $cfes = TesCfe::orderBy('fecha', 'asc')->get();

        $this->assertEquals('2026-08-10', $cfes->first()->fecha->format('Y-m-d'));
        $this->assertEquals('2026-08-20', $cfes->last()->fecha->format('Y-m-d'));
    }
}
