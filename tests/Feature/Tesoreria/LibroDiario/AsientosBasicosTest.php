<?php

namespace Tests\Feature\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Asientos Básicos del Libro Diario
 * 
 * Cubre:
 * - Registro de asientos de entrada
 * - Registro de asientos de salida
 * - Cálculo de saldos
 * - Numeración automática
 */
class AsientosBasicosTest extends TesoreriaTestCase
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

    public function test_puede_registrar_asiento_de_entrada(): void
    {
        $tipo = $this->getTipo('Entrada');

        $data = [
            'fecha' => '2026-08-01',
            'tipo_id' => $tipo->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ];

        $asiento = $this->service->registrarAsiento($data);

        $this->assertNotNull($asiento);
        $this->assertFloatEquals(5000.00, $asiento->monto);
        $this->assertEquals(1, $asiento->signo_efectivo);
        $this->assertFloatEquals(5000.00, $asiento->saldo);
        $this->assertAsientoCreado(['monto' => 5000.00]);
    }

    public function test_puede_registrar_asiento_de_salida(): void
    {
        // Primero crear entrada para tener saldo
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $asiento = $this->service->registrarSalida([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        $this->assertNotNull($asiento);
        $this->assertFloatEquals(1000.00, $asiento->monto);
        $this->assertEquals(-1, $asiento->signo_efectivo);
        // Saldo = 5000 - 1000 = 4000
        $this->assertFloatEquals(4000.00, $asiento->saldo);
    }

    public function test_asiento_con_factory_entrada(): void
    {
        $asiento = LibroDiario::factory()
            ->entrada()
            ->conMonto(3000.00)
            ->enFecha('2026-08-01')
            ->create();

        $this->assertEquals(1, $asiento->signo_efectivo);
        $this->assertFloatEquals(3000.00, $asiento->monto);
        $this->assertGreaterThan(0, $asiento->saldo);
    }

    public function test_asiento_con_factory_salida(): void
    {
        $asiento = LibroDiario::factory()
            ->salida()
            ->conMonto(1000.00)
            ->create();

        $this->assertEquals(-1, $asiento->signo_efectivo);
        $this->assertFloatEquals(1000.00, $asiento->monto);
        $this->assertLessThan(0, $asiento->saldo);
    }

    public function test_calcula_saldo_correctamente_con_multiple_entradas(): void
    {
        // Entrada 1
        $asiento1 = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        // Entrada 2
        $asiento2 = $this->service->registrarAsiento([
            'fecha' => '2026-08-05',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 3000.00,
        ]);

        // Saldo acumulado = 5000 + 3000 = 8000
        $this->assertFloatEquals(5000.00, $asiento1->saldo);
        $this->assertFloatEquals(8000.00, $asiento2->saldo);
    }

    public function test_calcula_saldo_correctamente_con_entradas_y_salidas(): void
    {
        // Entrada inicial
        $entrada = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        // Salida 1
        $salida1 = $this->service->registrarSalida([
            'fecha' => '2026-08-05',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        // Salida 2
        $salida2 = $this->service->registrarSalida([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 500.00,
        ]);

        // Saldos: 5000, 4000, 3500
        $this->assertFloatEquals(5000.00, $entrada->saldo);
        $this->assertFloatEquals(4000.00, $salida1->saldo);
        $this->assertFloatEquals(3500.00, $salida2->saldo);
    }

    public function test_genera_numero_automatico_por_anio_y_signo(): void
    {
        // Crear varios asientos del mismo año y signo
        $asiento1 = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        $asiento2 = $this->service->registrarAsiento([
            'fecha' => '2026-08-02',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 2000.00,
        ]);

        // Los números deben ser consecutivos
        $this->assertNotNull($asiento1->numero);
        $this->assertNotNull($asiento2->numero);
        $this->assertEquals($asiento1->numero + 1, $asiento2->numero);
    }

    public function test_numeros_independientes_por_signo(): void
    {
        // Entrada (signo 1)
        $entrada = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        // Salida (signo -1)
        $salida = $this->service->registrarSalida([
            'fecha' => '2026-08-02',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        // Ambos deberían tener numero 1 (son secuencias independientes)
        $this->assertEquals(1, $entrada->numero);
        $this->assertEquals(1, $salida->numero);
    }

    public function test_asiento_tiene_timestamps(): void
    {
        $asiento = LibroDiario::factory()->create();

        $this->assertNotNull($asiento->created_at);
        $this->assertNotNull($asiento->updated_at);
    }

    public function test_asiento_soporta_soft_delete(): void
    {
        $asiento = LibroDiario::factory()->create();
        $idAsiento = $asiento->id;

        $asiento->delete();

        $this->assertSoftDeleted('tes_libro_diario', ['id' => $idAsiento]);
    }

    public function test_puede_obtener_saldo_actual_de_flujo(): void
    {
        // Crear varios asientos
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $this->service->registrarSalida([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1500.00,
        ]);

        $saldo = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        // Saldo = 5000 - 1500 = 3500
        $this->assertFloatEquals(3500.00, $saldo);
    }

    public function test_saldo_flujo_sin_asientos_es_cero(): void
    {
        $saldo = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        $this->assertFloatEquals(0.00, $saldo);
    }

    public function test_asiento_puede_tener_descripcion(): void
    {
        $descripcion = 'Constitución de fondo fijo de caja chica';
        
        $asiento = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
            'descripcion' => $descripcion,
        ]);

        $this->assertEquals($descripcion, $asiento->descripcion);
    }

    public function test_asiento_puede_tener_documento_referencia(): void
    {
        $documento = 'REC-12345';
        
        $asiento = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
            'documento_referencia' => $documento,
        ]);

        $this->assertEquals($documento, $asiento->documento_referencia);
    }
}
