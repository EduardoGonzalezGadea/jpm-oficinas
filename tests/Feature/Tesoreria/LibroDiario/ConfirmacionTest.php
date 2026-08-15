<?php

namespace Tests\Feature\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Confirmación de Asientos del Libro Diario
 * 
 * Cubre:
 * - Confirmación de asientos
 * - Desconfirmación de asientos
 * - Toggle de confirmación
 * - Confirmación por documento
 * - Confirmación de redistribuciones
 */
class ConfirmacionTest extends TesoreriaTestCase
{
    private LibroDiarioService $service;
    private MedioDePago $medio;
    private LbConcepto $concepto;
    private LbDetalle $detalle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LibroDiarioService::class);
        
        $this->medio = $this->getMedioDePago('EF');
        $this->concepto = $this->getConcepto(LbConcepto::CAJA_CHICA);
        $this->detalle = $this->getDetalle(LbDetalle::FONDO_FIJO);
    }

    public function test_asiento_no_confirmado_por_defecto(): void
    {
        $asiento = LibroDiario::factory()->create();

        $this->assertFalse($asiento->confirmado);
        $this->assertNull($asiento->fecha_confirmacion);
    }

    public function test_puede_confirmar_asiento(): void
    {
        $asiento = LibroDiario::factory()->create();

        $this->service->confirmarEntrada($asiento->id);

        $asiento->refresh();
        $this->assertTrue($asiento->confirmado);
        $this->assertNotNull($asiento->fecha_confirmacion);
    }

    public function test_puede_desconfirmar_asiento(): void
    {
        $asiento = LibroDiario::factory()->confirmado()->create();

        $this->service->desconfirmarEntrada($asiento->id);

        $asiento->refresh();
        $this->assertFalse($asiento->confirmado);
        $this->assertNull($asiento->fecha_confirmacion);
    }

    public function test_toggle_confirmacion_confirma_si_no_confirmado(): void
    {
        $asiento = LibroDiario::factory()->create();

        $resultado = $this->service->toggleConfirmacion($asiento->id);

        $this->assertTrue($resultado);
        $asiento->refresh();
        $this->assertTrue($asiento->confirmado);
    }

    public function test_toggle_confirmacion_desconfirma_si_confirmado(): void
    {
        $asiento = LibroDiario::factory()->confirmado()->create();

        $resultado = $this->service->toggleConfirmacion($asiento->id);

        $this->assertFalse($resultado);
        $asiento->refresh();
        $this->assertFalse($asiento->confirmado);
    }

    public function test_confirmacion_con_fecha_especifica(): void
    {
        $asiento = LibroDiario::factory()->create();
        $fechaConfirmacion = '2026-08-15 10:30:00';

        $this->service->toggleConfirmacion($asiento->id, $fechaConfirmacion);

        $asiento->refresh();
        $this->assertEquals($fechaConfirmacion, $asiento->fecha_confirmacion->format('Y-m-d H:i:s'));
    }

    public function test_asiento_confirmado_con_factory(): void
    {
        $asiento = LibroDiario::factory()->confirmado()->create();

        $this->assertTrue($asiento->confirmado);
        $this->assertNotNull($asiento->fecha_confirmacion);
    }

    public function test_puede_confirmar_por_documento(): void
    {
        $documento = 'REC-12345';

        // Crear varios asientos con mismo documento
        $asiento1 = LibroDiario::factory()->create([
            'documento_referencia' => $documento,
        ]);

        $asiento2 = LibroDiario::factory()->create([
            'documento_referencia' => $documento,
        ]);

        $asiento3 = LibroDiario::factory()->create([
            'documento_referencia' => 'OTRO-DOC',
        ]);

        $count = $this->service->confirmarPorDocumento($documento);

        $this->assertEquals(2, $count);
        $this->assertTrue($asiento1->fresh()->confirmado);
        $this->assertTrue($asiento2->fresh()->confirmado);
        $this->assertFalse($asiento3->fresh()->confirmado);
    }

    public function test_puede_desconfirmar_por_documento(): void
    {
        $documento = 'REC-12345';

        // Crear asientos confirmados
        $asiento1 = LibroDiario::factory()->confirmado()->create([
            'documento_referencia' => $documento,
        ]);

        $asiento2 = LibroDiario::factory()->confirmado()->create([
            'documento_referencia' => $documento,
        ]);

        $count = $this->service->desconfirmarPorDocumento($documento);

        $this->assertEquals(2, $count);
        $this->assertFalse($asiento1->fresh()->confirmado);
        $this->assertFalse($asiento2->fresh()->confirmado);
    }

    public function test_confirmacion_de_redistribucion_afecta_ambos_asientos(): void
    {
        // Crear fondo
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        // Crear redistribución
        $resultado = $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->detalle->id,
                'medio_id' => $this->medio->id,
                'monto' => 1000.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
                'medio_id' => $this->medio->id,
            ]
        );

        $origen = $resultado['asientoOrigen'];
        $destino = $resultado['asientoDestino'];

        // Confirmar uno debe confirmar ambos
        $this->service->confirmarEntrada($origen->id);

        $this->assertTrue($origen->fresh()->confirmado);
        $this->assertTrue($destino->fresh()->confirmado);
    }

    public function test_desconfirmacion_de_redistribucion_afecta_ambos_asientos(): void
    {
        // Crear fondo
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        // Crear y confirmar redistribución
        $resultado = $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->detalle->id,
                'medio_id' => $this->medio->id,
                'monto' => 1000.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
                'medio_id' => $this->medio->id,
            ]
        );

        $origen = $resultado['asientoOrigen'];
        $destino = $resultado['asientoDestino'];

        $this->service->confirmarEntrada($origen->id);

        // Desconfirmar debe desconfirmar ambos
        $this->service->desconfirmarEntrada($origen->id);

        $this->assertFalse($origen->fresh()->confirmado);
        $this->assertFalse($destino->fresh()->confirmado);
    }

    public function test_scope_pendientes_filtra_no_confirmados(): void
    {
        LibroDiario::factory()->count(3)->create();
        LibroDiario::factory()->count(2)->confirmado()->create();

        $pendientes = LibroDiario::pendientes()->get();

        $this->assertCount(3, $pendientes);
    }

    public function test_scope_confirmados_filtra_confirmados(): void
    {
        LibroDiario::factory()->count(3)->create();
        LibroDiario::factory()->count(2)->confirmado()->create();

        $confirmados = LibroDiario::confirmados()->get();

        $this->assertCount(2, $confirmados);
    }

    public function test_fecha_efectiva_usa_fecha_confirmacion_si_confirmado(): void
    {
        $fechaCreacion = '2026-08-01';
        $fechaConfirmacion = '2026-08-15 10:00:00';

        $asiento = LibroDiario::factory()->create([
            'fecha' => $fechaCreacion,
        ]);

        $this->service->toggleConfirmacion($asiento->id, $fechaConfirmacion);

        $asiento->refresh();
        $this->assertEquals($fechaConfirmacion, $asiento->fechaEfectiva->format('Y-m-d H:i:s'));
    }

    public function test_fecha_efectiva_usa_fecha_si_no_confirmado(): void
    {
        $fecha = '2026-08-01';

        $asiento = LibroDiario::factory()->create([
            'fecha' => $fecha,
        ]);

        // Convertir a Carbon para comparar
        $fechaEsperada = \Carbon\Carbon::parse($fecha);
        $this->assertEquals($fechaEsperada->format('Y-m-d'), $asiento->fechaEfectiva->format('Y-m-d'));
    }

    public function test_metodo_confirmar_en_modelo(): void
    {
        $asiento = LibroDiario::factory()->create();

        $asiento->confirmar('2026-08-20 14:30:00');

        $this->assertTrue($asiento->fresh()->confirmado);
        $this->assertEquals('2026-08-20 14:30:00', $asiento->fresh()->fecha_confirmacion->format('Y-m-d H:i:s'));
    }

    public function test_helper_assertion_asiento_confirmado(): void
    {
        $asiento = LibroDiario::factory()->confirmado()->create();

        $this->assertAsientoConfirmado($asiento->id);
    }

    public function test_helper_assertion_asiento_no_confirmado(): void
    {
        $asiento = LibroDiario::factory()->create();

        $this->assertAsientoNoConfirmado($asiento->id);
    }
}
