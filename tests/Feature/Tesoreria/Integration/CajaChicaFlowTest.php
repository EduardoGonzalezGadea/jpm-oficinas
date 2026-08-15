<?php

namespace Tests\Feature\Tesoreria\Integration;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Services\Tesoreria\LibroDiarioService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Integración: Flujo Completo de Caja Chica
 * 
 * Flujo:
 * 1. Constitución de Caja Chica → Asiento en Libro Diario
 * 2. Creación de Pagos/Pendientes
 * 3. Rendición de gastos → Asientos
 * 4. Recuperación → Asientos de reintegro
 * 5. Verificación de saldos en Libro Diario
 */
class CajaChicaFlowTest extends TesoreriaTestCase
{
    private LibroDiarioService $libroService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->libroService = app(LibroDiarioService::class);
    }

    public function test_flujo_completo_constitucion_caja_chica(): void
    {
        // 1. Constituir caja chica
        $caja = CajaChica::factory()->create([
            'mes' => 'agosto',
            'anio' => 2026,
            'montoCajaChica' => 10000.00,
        ]);

        // 2. Registrar asiento de constitución
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 10000.00,
            'documento_referencia' => 'CONST-' . $caja->id,
        ]);

        // Verificaciones
        $this->assertNotNull($asiento);
        $this->assertFloatEquals(10000.00, $asiento->monto);
        $this->assertFloatEquals(10000.00, $asiento->saldo);
        $this->assertEquals('CONST-' . $caja->id, $asiento->documento_referencia);
    }

    public function test_flujo_completo_con_pagos_y_rendicion(): void
    {
        // 1. Crear caja chica
        $caja = CajaChica::factory()->create([
            'montoCajaChica' => 10000.00,
        ]);

        // 2. Registrar constitución en libro
        $this->libroService->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 10000.00,
        ]);

        // 3. Crear pagos
        $pago1 = Pago::factory()->paraCajaChica($caja)->create([
            'montoPagos' => 2000.00,
            'rendidoPagos' => 0,
        ]);

        $pago2 = Pago::factory()->paraCajaChica($caja)->create([
            'montoPagos' => 1500.00,
            'rendidoPagos' => 0,
        ]);

        // 4. Rendir pagos
        $pago1->update(['rendidoPagos' => 1800.00]); // Reintegro: 200
        $pago2->update(['rendidoPagos' => 1500.00]); // Sin reintegro

        // 5. Registrar asientos de rendición
        $asientoRendicion = $this->libroService->registrarSalida([
            'fecha' => '2026-08-10',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PAGOS)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 3300.00, // Total rendido
        ]);

        // 6. Verificar saldos
        $saldoFinal = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::FONDO_FIJO)->id);
        $this->assertFloatEquals(10000.00, $saldoFinal); // Fondo sigue intacto

        $saldoPagos = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::PAGOS)->id);
        $this->assertFloatEquals(-3300.00, $saldoPagos); // Salida de pagos

        // 7. Verificar totales de caja
        $totalPagos = $caja->pagos->sum('montoPagos');
        $totalRendido = $caja->pagos->sum('rendidoPagos');
        $this->assertFloatEquals(3500.00, $totalPagos);
        $this->assertFloatEquals(3300.00, $totalRendido);
    }

    public function test_flujo_completo_con_pendientes_y_movimientos(): void
    {
        // 1. Crear caja chica
        $caja = CajaChica::factory()->create([
            'montoCajaChica' => 10000.00,
        ]);

        // 2. Constitución
        $this->libroService->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 10000.00,
        ]);

        // 3. Crear pendiente
        $pendiente = Pendiente::factory()->paraCajaChica($caja)->create([
            'montoPendientes' => 5000.00,
        ]);

        // 4. Crear movimientos del pendiente
        $movimiento1 = Movimiento::factory()->create([
            'caja_chica_id' => $caja->id,
            'pendiente_id' => $pendiente->id,
            'montoPendientes' => 2000.00,
            'rendidoPendientes' => 0,
        ]);

        $movimiento2 = Movimiento::factory()->create([
            'caja_chica_id' => $caja->id,
            'pendiente_id' => $pendiente->id,
            'montoPendientes' => 3000.00,
            'rendidoPendientes' => 0,
        ]);

        // 5. Rendir movimientos
        $movimiento1->update(['rendidoPendientes' => 1800.00]);
        $movimiento2->update(['rendidoPendientes' => 2900.00]);

        // 6. Registrar asiento de rendición de pendientes
        $asientoRendicionPend = $this->libroService->registrarSalida([
            'fecha' => '2026-08-15',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4700.00, // Total rendido
        ]);

        // 7. Verificar saldos
        $saldoPendiente = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::PENDIENTE)->id);
        $this->assertFloatEquals(-4700.00, $saldoPendiente);

        // 8. Recuperar pendiente
        $movimiento1->update(['recuperadoPendientes' => 1800.00]);
        $movimiento2->update(['recuperadoPendientes' => 2900.00]);

        // 9. Registrar asiento de recuperación
        $asientoRecuperacion = $this->libroService->registrarAsiento([
            'fecha' => '2026-08-20',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4700.00,
        ]);

        // 10. Verificar saldo final de pendientes (debe volver a 0)
        $saldoFinalPendiente = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::PENDIENTE)->id);
        $this->assertFloatEquals(0.00, $saldoFinalPendiente);
    }

    public function test_flujo_completo_caja_chica_mes_completo(): void
    {
        // Simular un mes completo de caja chica

        // 1. Constituir caja
        $caja = CajaChica::factory()->create([
            'mes' => 'agosto',
            'anio' => 2026,
            'montoCajaChica' => 15000.00,
        ]);

        $this->libroService->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 15000.00,
        ]);

        // 2. Semana 1: Pagos
        $pago1 = Pago::factory()->paraCajaChica($caja)->create([
            'montoPagos' => 2500.00,
        ]);

        // 3. Semana 2: Más pagos y pendientes
        $pago2 = Pago::factory()->paraCajaChica($caja)->create([
            'montoPagos' => 1800.00,
        ]);

        $pendiente = Pendiente::factory()->paraCajaChica($caja)->create([
            'montoPendientes' => 5000.00,
        ]);

        $movimiento = Movimiento::factory()->create([
            'caja_chica_id' => $caja->id,
            'pendiente_id' => $pendiente->id,
            'montoPendientes' => 5000.00,
        ]);

        // 4. Semana 3: Rendiciones
        $pago1->update(['rendidoPagos' => 2400.00]);
        $pago2->update(['rendidoPagos' => 1800.00]);
        $movimiento->update(['rendidoPendientes' => 4800.00]);

        // Asientos de rendición
        $this->libroService->registrarSalida([
            'fecha' => '2026-08-15',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PAGOS)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4200.00, // Pagos rendidos
        ]);

        $this->libroService->registrarSalida([
            'fecha' => '2026-08-16',
            'tipo_id' => $this->getTipo('Salida')->id,
            'signo_efectivo' => -1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4800.00, // Pendientes rendidos
        ]);

        // 5. Semana 4: Recuperaciones
        $pago1->update(['recuperadoPagos' => 2400.00]);
        $pago2->update(['recuperadoPagos' => 1800.00]);
        $movimiento->update(['recuperadoPendientes' => 4800.00]);

        // Asientos de recuperación
        $this->libroService->registrarAsiento([
            'fecha' => '2026-08-25',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PAGOS)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4200.00,
        ]);

        $this->libroService->registrarAsiento([
            'fecha' => '2026-08-26',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4800.00,
        ]);

        // 6. Verificaciones finales
        $saldoFondo = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::FONDO_FIJO)->id);
        $saldoPagos = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::PAGOS)->id);
        $saldoPendientes = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::PENDIENTE)->id);

        $this->assertFloatEquals(15000.00, $saldoFondo); // Fondo intacto
        $this->assertFloatEquals(0.00, $saldoPagos); // Recuperado completamente
        $this->assertFloatEquals(0.00, $saldoPendientes); // Recuperado completamente

        // 7. Verificar totales de caja chica
        $totalPagos = $caja->fresh()->pagos->sum('montoPagos');
        $totalRendidoPagos = $caja->pagos->sum('rendidoPagos');
        $totalRecuperadoPagos = $caja->pagos->sum('recuperadoPagos');

        $this->assertFloatEquals(4300.00, $totalPagos);
        $this->assertFloatEquals(4200.00, $totalRendidoPagos);
        $this->assertFloatEquals(4200.00, $totalRecuperadoPagos);

        // Reintegro pendiente
        $reintegro = $totalPagos - $totalRendidoPagos;
        $this->assertFloatEquals(100.00, $reintegro);
    }

    public function test_redistribucion_entre_subcuentas_caja_chica(): void
    {
        // 1. Crear caja y constituir
        $caja = CajaChica::factory()->create(['montoCajaChica' => 10000.00]);

        $this->libroService->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
            'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 10000.00,
        ]);

        // 2. Redistribución: Fondo → Pendiente
        $resultado = $this->libroService->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
                'detalle_id' => $this->getDetalle(LbDetalle::FONDO_FIJO)->id,
                'medio_id' => $this->getMedioDePago('EF')->id,
                'monto' => 3000.00,
            ],
            [
                'concepto_id' => $this->getConcepto(LbConcepto::CAJA_CHICA)->id,
                'detalle_id' => $this->getDetalle(LbDetalle::PENDIENTE)->id,
                'medio_id' => $this->getMedioDePago('EF')->id,
            ]
        );

        // 3. Verificar saldos
        $saldoFondo = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::FONDO_FIJO)->id);
        $saldoPendiente = $this->getSaldoSubcuenta($this->getDetalle(LbDetalle::PENDIENTE)->id);

        $this->assertFloatEquals(7000.00, $saldoFondo); // 10000 - 3000
        $this->assertFloatEquals(3000.00, $saldoPendiente); // +3000

        // 4. Verificar que ambos asientos están vinculados
        $this->assertEquals(
            $resultado['asientoOrigen']->grupo_redistribucion,
            $resultado['asientoDestino']->grupo_redistribucion
        );
    }
}
