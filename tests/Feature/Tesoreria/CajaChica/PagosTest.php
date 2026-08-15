<?php

namespace Tests\Feature\Tesoreria\CajaChica;

use App\Models\Tesoreria\Acreedor;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pago;
use App\Services\Tesoreria\CajaChicaService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Pagos de Caja Chica
 * 
 * Cubre:
 * - Creación de pagos
 * - Rendición de pagos
 * - Recuperación de pagos
 * - Cálculos de saldos
 * - Regla especial BSE
 */
class PagosTest extends TesoreriaTestCase
{
    private CajaChicaService $service;
    private CajaChica $caja;
    private Acreedor $acreedor;
    private Acreedor $bse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CajaChicaService::class);
        $this->caja = CajaChica::factory()->conMonto(5000)->create();
        $this->acreedor = Acreedor::factory()->create();
        $this->bse = $this->crearAcreedorBSE();
    }

    public function test_puede_crear_pago(): void
    {
        $data = [
            'relCajaChica_Pagos' => $this->caja->idCajaChica,
            'fechaEgresoPagos' => '2026-08-10',
            'egresoPagos' => 'EGR-001',
            'relAcreedores' => $this->acreedor->idAcreedores,
            'conceptoPagos' => 'Compra de insumos',
            'montoPagos' => 1000.00,
        ];

        $pago = $this->service->crearPago($data);

        $this->assertNotNull($pago);
        $this->assertPagoCreado([
            'relCajaChica_Pagos' => $this->caja->idCajaChica,
            'montoPagos' => 1000.00,
        ]);
    }

    public function test_pago_con_factory_basic(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->paraAcreedor($this->acreedor)
            ->conMonto(1500.00)
            ->create();

        $this->assertEquals($this->caja->idCajaChica, $pago->relCajaChica_Pagos);
        $this->assertEquals($this->acreedor->idAcreedores, $pago->relAcreedores);
        $this->assertFloatEquals(1500.00, $pago->montoPagos);
    }

    public function test_pago_normaliza_referencias_a_mayusculas(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->create(['egresoPagos' => 'egr-001']);

        $this->assertEquals('EGR-001', $pago->egresoPagos);
    }

    public function test_puede_rendir_pago_completo(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->create();

        $data = [
            'rendidoPagos' => 1000.00,
            'reintegradoPagos' => null,
            'fechaRendicionPagos' => '2026-08-20',
            'ingresoReintegroPagos' => null,
        ];

        $this->service->guardarRendicionPago([
            'idPago' => $pago->idPagos,
            ...$data,
        ]);

        $pago->refresh();
        $this->assertFloatEquals(1000.00, $pago->rendidoPagos);
        $this->assertNull($pago->reintegradoPagos);
        $this->assertTrue($pago->tieneDatosRendicion());
    }

    public function test_puede_rendir_pago_parcial_con_reintegro(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->create();

        $this->service->guardarRendicionPago([
            'idPago' => $pago->idPagos,
            'rendidoPagos' => 850.00,
            'reintegradoPagos' => 150.00,
            'fechaRendicionPagos' => '2026-08-20',
            'ingresoReintegroPagos' => 'REINT-001',
        ]);

        $pago->refresh();
        $this->assertFloatEquals(850.00, $pago->rendidoPagos);
        $this->assertFloatEquals(150.00, $pago->reintegradoPagos);
        $this->assertEquals('REINT-001', $pago->ingresoReintegroPagos);
    }

    public function test_pago_rendido_con_factory(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->rendido(800.00, '2026-08-15')
            ->create();

        $this->assertFloatEquals(800.00, $pago->rendidoPagos);
        $this->assertFloatEquals(200.00, $pago->reintegradoPagos); // Del monto original 1000
        $this->assertTrue($pago->tieneDatosRendicion());
    }

    public function test_calculo_saldo_pago_sin_rendir(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->create();

        // Saldo = monto otorgado - recuperado (0)
        $this->assertFloatEquals(1000.00, $pago->saldoPagos);
    }

    public function test_calculo_saldo_pago_rendido(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->rendido(850)
            ->create();

        // Saldo = rendido (850) - recuperado (0)
        $this->assertFloatEquals(850.00, $pago->saldoPagos);
    }

    public function test_calculo_saldo_pago_recuperado_parcial(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->rendido(850)
            ->recuperado(400)
            ->create();

        // Saldo = rendido (850) - recuperado (400)
        $this->assertFloatEquals(450.00, $pago->saldoPagos);
    }

    public function test_calculo_saldo_pago_recuperado_total(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->rendido(850)
            ->recuperado(850)
            ->create();

        // Saldo = 0 cuando se recuperó todo
        $this->assertFloatEquals(0.00, $pago->saldoPagos);
    }

    public function test_puede_recuperar_pago_normal(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->paraAcreedor($this->acreedor) // NO BSE
            ->conMonto(1000)
            ->rendido(800)
            ->create();

        $this->service->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-25',
            'numero_ingreso' => 'ING-001',
            'numero_ingreso_bse' => null,
            'fecha_ingreso_bse' => null,
        ]);

        $pago->refresh();
        $this->assertFloatEquals(400.00, $pago->recuperadoPagos);
        $this->assertEquals('ING-001', $pago->ingresoPagos);
        $this->assertTrue($pago->tieneDatosRecuperacion());
    }

    public function test_regla_bse_con_datos_bse_no_genera_asiento(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->paraAcreedor($this->bse)
            ->conMonto(1000)
            ->rendido(800)
            ->create();

        // Contar asientos ANTES
        $asientosAntes = $this->contarAsientos([
            'cch_origen_type' => 'pago',
            'cch_origen_id' => $pago->idPagos,
        ]);

        $this->service->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-25',
            'numero_ingreso' => 'ING-001',
            'numero_ingreso_bse' => 'BSE-2026-001', // CON datos BSE
            'fecha_ingreso_bse' => '2026-08-25',
        ]);

        // NO debe crear asiento de recuperación (regla BSE)
        $asientosDespues = $this->contarAsientos([
            'cch_origen_type' => 'pago',
            'cch_origen_id' => $pago->idPagos,
        ]);

        $this->assertEquals($asientosAntes, $asientosDespues);
        
        $pago->refresh();
        $this->assertEquals('BSE-2026-001', $pago->ingresoPagosBSE);
    }

    public function test_regla_bse_sin_datos_bse_genera_asiento(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->paraAcreedor($this->bse)
            ->conMonto(1000)
            ->rendido(800)
            ->create();

        $asientosAntes = $this->contarAsientos([
            'cch_origen_type' => 'pago',
            'cch_origen_id' => $pago->idPagos,
        ]);

        $this->service->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-25',
            'numero_ingreso' => 'ING-001',
            'numero_ingreso_bse' => null, // SIN datos BSE
            'fecha_ingreso_bse' => null,
        ]);

        // DEBE crear asiento de recuperación
        $asientosDespues = $this->contarAsientos([
            'cch_origen_type' => 'pago',
            'cch_origen_id' => $pago->idPagos,
        ]);

        $this->assertGreaterThan($asientosAntes, $asientosDespues);
    }

    public function test_pago_completo_con_factory(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->completo(true) // rendido + recuperado con datos BSE
            ->create();

        $this->assertNotNull($pago->rendidoPagos);
        $this->assertNotNull($pago->recuperadoPagos);
        $this->assertTrue($pago->tieneDatosRendicion());
        $this->assertTrue($pago->tieneDatosRecuperacion());
    }

    public function test_puede_actualizar_pago(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->create();

        $pagoActualizado = $this->service->actualizarPago($pago->idPagos, [
            'fechaEgresoPagos' => '2026-08-15',
            'fechaEgresoEfectivoPagos' => null,
            'egresoPagos' => 'EGR-002',
            'relAcreedores' => $this->acreedor->idAcreedores,
            'conceptoPagos' => 'Concepto actualizado',
            'montoPagos' => 1200.00,
            'rendidoPagos' => null,
            'reintegradoPagos' => null,
            'ingresoReintegroPagos' => null,
            'fechaRendicionPagos' => null,
            'recuperadoPagos' => null,
            'fechaIngresoPagos' => null,
            'ingresoPagos' => null,
            'ingresoPagosBSE' => null,
            'fechaIngresoBSEPagos' => null,
        ]);

        $this->assertEquals('EGR-002', $pagoActualizado->egresoPagos);
        $this->assertEquals('Concepto actualizado', $pagoActualizado->conceptoPagos);
        $this->assertFloatEquals(1200.00, $pagoActualizado->montoPagos);
    }

    public function test_puede_eliminar_pago(): void
    {
        $pago = Pago::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $idPago = $pago->idPagos;

        $this->service->eliminarPago($idPago);

        $this->assertSoftDeleted('tes_cch_pagos', ['idPagos' => $idPago]);
    }

    public function test_pago_puede_verificar_si_puede_recuperar(): void
    {
        $pagoSinRendir = Pago::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $pagoRendido = Pago::factory()
            ->paraCajaChica($this->caja)
            ->rendido()
            ->create();

        $pagoRecuperado = Pago::factory()
            ->paraCajaChica($this->caja)
            ->completo()
            ->create();

        $this->assertFalse($pagoSinRendir->puedeRecuperar());
        $this->assertTrue($pagoRendido->puedeRecuperar());
        $this->assertFalse($pagoRecuperado->puedeRecuperar());
    }
}
