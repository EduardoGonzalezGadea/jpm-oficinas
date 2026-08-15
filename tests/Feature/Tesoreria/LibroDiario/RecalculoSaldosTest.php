<?php

namespace Tests\Feature\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Recálculo de Saldos del Libro Diario
 * 
 * Cubre:
 * - Recálculo de saldos de subcuentas
 * - Corrección de inconsistencias
 * - Saldos históricos
 * - Validación de saldos
 */
class RecalculoSaldosTest extends TesoreriaTestCase
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

    public function test_recalcula_saldos_correctamente(): void
    {
        // Crear asientos con saldos
        $asiento1 = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $asiento2 = $this->service->registrarSalida([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        // Corromper saldos manualmente
        $asiento1->update(['saldo' => 0]);
        $asiento2->update(['saldo' => 0]);

        // Recalcular
        $this->service->recalcularSaldosSubcuenta(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        // Verificar que se corrigieron
        $this->assertFloatEquals(5000.00, $asiento1->fresh()->saldo);
        $this->assertFloatEquals(4000.00, $asiento2->fresh()->saldo);
    }

    public function test_recalcula_saldos_con_multiples_asientos(): void
    {
        // Crear serie de asientos
        $asientos = [];
        
        $asientos[] = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $asientos[] = $this->service->registrarSalida([
            'fecha' => '2026-08-05',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        $asientos[] = $this->service->registrarAsiento([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 2000.00,
        ]);

        $asientos[] = $this->service->registrarSalida([
            'fecha' => '2026-08-15',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 500.00,
        ]);

        // Corromper todos los saldos
        foreach ($asientos as $asiento) {
            $asiento->update(['saldo' => 999.99]);
        }

        // Recalcular
        $this->service->recalcularSaldosSubcuenta(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        // Verificar saldos correctos: 5000, 4000, 6000, 5500
        $this->assertFloatEquals(5000.00, $asientos[0]->fresh()->saldo);
        $this->assertFloatEquals(4000.00, $asientos[1]->fresh()->saldo);
        $this->assertFloatEquals(6000.00, $asientos[2]->fresh()->saldo);
        $this->assertFloatEquals(5500.00, $asientos[3]->fresh()->saldo);
    }

    public function test_recalculo_respeta_orden_cronologico(): void
    {
        // Crear asientos en desorden
        $asiento3 = $this->service->registrarAsiento([
            'fecha' => '2026-08-15',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        $asiento1 = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $asiento2 = $this->service->registrarAsiento([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 2000.00,
        ]);

        // Corromper
        $asiento1->update(['saldo' => 0]);
        $asiento2->update(['saldo' => 0]);
        $asiento3->update(['saldo' => 0]);

        // Recalcular
        $this->service->recalcularSaldosSubcuenta(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        // Saldos deben ser: 5000 (01), 7000 (10), 8000 (15)
        $this->assertFloatEquals(5000.00, $asiento1->fresh()->saldo);
        $this->assertFloatEquals(7000.00, $asiento2->fresh()->saldo);
        $this->assertFloatEquals(8000.00, $asiento3->fresh()->saldo);
    }

    public function test_saldo_actual_flujo_refleja_ultimo_asiento(): void
    {
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

        $saldoActual = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        // Debe ser el saldo del último asiento: 5000 - 1500 = 3500
        $this->assertFloatEquals(3500.00, $saldoActual);
    }

    public function test_helper_assertion_saldo_subcuenta(): void
    {
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $this->assertSaldoSubcuenta($this->detalle->id, 5000.00);
    }

    public function test_helper_get_saldo_subcuenta(): void
    {
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 3000.00,
        ]);

        $saldo = $this->getSaldoSubcuenta($this->detalle->id);

        $this->assertFloatEquals(3000.00, $saldo);
    }

    public function test_recalculo_con_asientos_eliminados_los_ignora(): void
    {
        $asiento1 = $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $asiento2 = $this->service->registrarAsiento([
            'fecha' => '2026-08-05',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 2000.00,
        ]);

        $asiento3 = $this->service->registrarAsiento([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        // Eliminar el segundo asiento (soft delete)
        $asiento2->delete();

        // Recalcular
        $this->service->recalcularSaldosSubcuenta(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        );

        // Saldos deben ser: 5000, (eliminado), 6000
        $this->assertFloatEquals(5000.00, $asiento1->fresh()->saldo);
        $this->assertFloatEquals(6000.00, $asiento3->fresh()->saldo);
    }

    public function test_saldos_actuales_por_flujo_retorna_subcuentas(): void
    {
        // Crear saldos en diferentes subcuentas
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $this->service->registrarAsiento([
            'fecha' => '2026-08-05',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ]);

        $saldos = $this->service->saldosActualesPorFlujo();

        $this->assertNotEmpty($saldos);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $saldos);
    }
}
