<?php

namespace Tests\Feature\Tesoreria\CajaChica;

use App\Models\Tesoreria\Acreedor;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\Pago;
use App\Services\Tesoreria\CajaChicaService;
use App\Services\Tesoreria\LibroDiarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaChicaReglaBseTest extends TestCase
{
    use RefreshDatabase;

    private CajaChicaService $servicio;
    private Acreedor $acreedorBSE;
    private Acreedor $acreedorOtro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(CajaChicaService::class);

        LbTipo::create(['nombre' => 'Entrada', 'signo' => 1]);
        LbTipo::create(['nombre' => 'Salida', 'signo' => -1]);
        LbTipo::create(['nombre' => 'Redistribución', 'signo' => 0]);

        $concepto = LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);

        LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_222]);
        LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_DIARIA]);

        LbDetalle::create(['concepto_id' => $concepto->id, 'nombre' => LbDetalle::FONDO_FIJO]);
        LbDetalle::create(['concepto_id' => $concepto->id, 'nombre' => LbDetalle::PENDIENTE]);
        LbDetalle::create(['concepto_id' => $concepto->id, 'nombre' => LbDetalle::PAGOS]);

        MedioDePago::create([
            'nombre' => 'Efectivo',
            'nombre_corto' => 'EF',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);

        CajaChica::create(['mes' => 'agosto', 'anio' => 2026, 'montoCajaChica' => 5000]);

        $this->acreedorBSE = Acreedor::create(['acreedor' => 'Banco de Seguros del Estado']);
        $this->acreedorOtro = Acreedor::create(['acreedor' => 'Proveedor X']);
    }

    private function crearPago(Acreedor $acreedor = null): Pago
    {
        $caja = CajaChica::first();

        return Pago::create([
            'relCajaChica_Pagos' => $caja->idCajaChica,
            'fechaEgresoPagos' => '2026-08-03',
            'egresoPagos' => 'EGR-001',
            'conceptoPagos' => 'Compra de insumos',
            'montoPagos' => 1000,
            'relAcreedores' => $acreedor ? $acreedor->idAcreedores : $this->acreedorOtro->idAcreedores,
        ]);
    }

    private function contarAsientosDePago(Pago $pago): int
    {
        return LibroDiario::where('cch_origen_type', 'pago')
            ->where('cch_origen_id', $pago->idPagos)
            ->count();
    }

    public function test_recupera_pago_bse_con_campos_bse_sin_generar_asiento(): void
    {
        $pago = $this->crearPago($this->acreedorBSE);

        $this->servicio->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-20',
            'numero_ingreso' => 'INGRESO 123',
            'numero_ingreso_bse' => 'BSE-2026-001',
            'fecha_ingreso_bse' => '2026-08-20',
        ]);

        $this->assertDatabaseHas('tes_cch_pagos', [
            'idPagos' => $pago->idPagos,
            'recuperadoPagos' => 400,
            'ingresoPagosBSE' => 'BSE-2026-001',
        ]);

        $this->assertSame(0, $this->contarAsientosDePago($pago));
    }

    public function test_recuperación_pago_bse_sin_campos_bse_genera_asiento(): void
    {
        $pago = $this->crearPago($this->acreedorBSE);

        $this->servicio->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-20',
            'numero_ingreso' => 'INGRESO 123',
            'numero_ingreso_bse' => null,
            'fecha_ingreso_bse' => null,
        ]);

        $this->assertSame(1, $this->contarAsientosDePago($pago));
    }

    public function test_recuperación_pago_no_bse_genera_asiento(): void
    {
        $pago = $this->crearPago($this->acreedorOtro);

        $this->servicio->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-20',
            'numero_ingreso' => 'INGRESO 123',
            'numero_ingreso_bse' => null,
            'fecha_ingreso_bse' => null,
        ]);

        $this->assertSame(1, $this->contarAsientosDePago($pago));
    }

    public function test_actualizar_pago_bse_con_campos_bse_no_genera_asiento_de_recuperación(): void
    {
        $pago = $this->crearPago($this->acreedorBSE);

        $this->servicio->actualizarPago($pago->idPagos, [
            'fechaEgresoPagos' => '2026-08-15',
            'fechaEgresoEfectivoPagos' => null,
            'egresoPagos' => 'EGRESO-001',
            'relAcreedores' => $this->acreedorBSE->idAcreedores,
            'conceptoPagos' => 'Compra de insumos',
            'montoPagos' => 1000,
            'recuperadoPagos' => 600,
            'fechaIngresoPagos' => '2026-08-20',
            'ingresoPagos' => 'INGRESO 123',
            'ingresoPagosBSE' => 'BSE-2026-001',
            'fechaIngresoBSEPagos' => '2026-08-20',
        ]);

        $this->assertSame(0, $this->contarAsientosDePago($pago));
    }

    public function test_editar_solo_campos_bse_en_pago_con_redistribucion() : void
    {
        // Se constituye el fondo con saldo justo (sin excedente) para que, si el
        // flujo intentara regenerar la redistribución, falle con
        // "monto a redistribuir supera el saldo disponible" (como en producción).
        $this->constituirFondoFijo();

        $pago = $this->crearPago($this->acreedorBSE);
        $asientosService = app(\App\Services\Tesoreria\CajaChicaAsientosService::class);

        // 1. El pago se crea y se registra su redistribución de fondo (asiento base).
        $asientosService->registrarRedistribucionPago($pago);

        // 2. Se registra la recuperación del BSE (no genera asientos por regla).
        $this->servicio->guardarRecuperacionPago([
            'relPago' => $pago->idPagos,
            'monto_recuperado' => 400,
            'fecha' => '2026-08-20',
            'numero_ingreso' => 'INGRESO 123',
            'numero_ingreso_bse' => 'BSE-2026-001',
            'fecha_ingreso_bse' => '2026-08-20',
        ]);

        $idsAntes = LibroDiario::where('cch_origen_type', 'pago')
            ->where('cch_origen_id', $pago->idPagos)
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        // 3. El usuario edita SOLO el ingreso BSE y su fecha.
        $this->servicio->actualizarPago($pago->idPagos, [
            'fechaEgresoPagos' => '2026-08-03',
            'fechaEgresoEfectivoPagos' => null,
            'egresoPagos' => 'EGR-001',
            'relAcreedores' => $this->acreedorBSE->idAcreedores,
            'conceptoPagos' => 'Compra de insumos',
            'montoPagos' => 1000,
            'recuperadoPagos' => 400,
            'fechaIngresoPagos' => '2026-08-20',
            'ingresoPagos' => 'INGRESO 123',
            'ingresoPagosBSE' => 'BSE-2026-002',
            'fechaIngresoBSEPagos' => '2026-08-21',
        ]);

        $pago->refresh();
        $this->assertSame('BSE-2026-002', $pago->ingresoPagosBSE);
        $this->assertSame('2026-08-21', $pago->fechaIngresoBSEPagos->format('Y-m-d'));

        // No debe regenerarse la redistribución: los asientos existentes
        // (base del fondo) permanecen intactos y no se crean otros.
        $idsDespues = LibroDiario::where('cch_origen_type', 'pago')
            ->where('cch_origen_id', $pago->idPagos)
            ->pluck('id')
            ->sort()
            ->values()
            ->toArray();

        $this->assertSame($idsAntes, $idsDespues);
    }

    private function constituirFondoFijo(): void
    {
        $concepto = LbConcepto::cajaChica();
        $detalle = LbDetalle::fondoFijo();
        $medio = MedioDePago::efectivo();

        app(LibroDiarioService::class)->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => LbTipo::where('nombre', 'Entrada')->first()->id,
            'signo_efectivo' => 1,
            'concepto_id' => $concepto->id,
            'detalle_id' => $detalle->id,
            'medio_id' => $medio->id,
            'monto' => 5000,
        ]);
    }
}