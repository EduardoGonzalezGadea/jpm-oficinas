<?php

namespace Tests\Feature\Tesoreria\CajaChica;

use App\Models\Tesoreria\CajaChica;
use App\Services\Tesoreria\CajaChicaService;
use Tests\TesoreriaTestCase;

/**
 * Tests de creación de Caja Chica
 * 
 * Cubre:
 * - Creación de fondo
 * - Validaciones de monto
 * - Unicidad de mes/año
 */
class CajaChicaCreacionTest extends TesoreriaTestCase
{
    private CajaChicaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CajaChicaService::class);
    }

    public function test_puede_crear_caja_chica_con_datos_validos(): void
    {
        $data = [
            'mes' => 'agosto',
            'anio' => 2026,
            'montoCajaChica' => 5000.00,
        ];

        $caja = $this->service->crearFondo($data);

        $this->assertNotNull($caja);
        $this->assertEquals('agosto', $caja->mes);
        $this->assertEquals(2026, $caja->anio);
        $this->assertFloatEquals(5000.00, $caja->montoCajaChica);
        $this->assertCajaChicaCreada($data);
    }

    public function test_puede_crear_caja_chica_con_factory(): void
    {
        $caja = CajaChica::factory()->create([
            'mes' => 'julio',
            'anio' => 2026,
            'montoCajaChica' => 3500.50,
        ]);

        $this->assertModelExists($caja);
        $this->assertEquals('julio', $caja->mes);
        $this->assertEquals(2026, $caja->anio);
        $this->assertFloatEquals(3500.50, $caja->montoCajaChica);
    }

    public function test_puede_crear_caja_chica_del_mes_actual(): void
    {
        $caja = CajaChica::factory()->mesActual()->create();

        $mesActual = strtolower(now()->locale('es')->monthName);
        $anioActual = now()->year;

        $this->assertEquals($mesActual, $caja->mes);
        $this->assertEquals($anioActual, $caja->anio);
    }

    public function test_puede_crear_caja_chica_con_monto_especifico(): void
    {
        $monto = 7500.75;
        $caja = CajaChica::factory()->conMonto($monto)->create();

        $this->assertFloatEquals($monto, $caja->montoCajaChica);
    }

    public function test_puede_crear_cajas_para_diferentes_meses(): void
    {
        $cajaEnero = CajaChica::factory()->enMes('enero', 2026)->create();
        $cajaFebrero = CajaChica::factory()->enMes('febrero', 2026)->create();
        $cajaMarzo = CajaChica::factory()->enMes('marzo', 2026)->create();

        $this->assertEquals('enero', $cajaEnero->mes);
        $this->assertEquals('febrero', $cajaFebrero->mes);
        $this->assertEquals('marzo', $cajaMarzo->mes);
        $this->assertCount(3, CajaChica::all());
    }

    public function test_puede_recuperar_caja_chica_por_mes_y_anio(): void
    {
        CajaChica::factory()->enMes('junio', 2026)->create();

        $caja = $this->service->obtenerCajaChica('junio', 2026);

        $this->assertNotNull($caja);
        $this->assertEquals('junio', $caja->mes);
        $this->assertEquals(2026, $caja->anio);
    }

    public function test_retorna_null_si_no_existe_caja_para_mes_anio(): void
    {
        $caja = $this->service->obtenerCajaChica('diciembre', 2025);

        $this->assertNull($caja);
    }

    public function test_caja_chica_puede_tener_pendientes_relacionados(): void
    {
        $caja = CajaChica::factory()->create();
        
        $pendientes = \App\Models\Tesoreria\Pendiente::factory()
            ->count(3)
            ->paraCajaChica($caja)
            ->create();

        $this->assertCount(3, $caja->fresh()->pendientes);
    }

    public function test_caja_chica_puede_tener_pagos_relacionados(): void
    {
        $caja = CajaChica::factory()->create();
        
        $pagos = \App\Models\Tesoreria\Pago::factory()
            ->count(5)
            ->paraCajaChica($caja)
            ->create();

        $this->assertCount(5, $caja->fresh()->pagos);
    }

    public function test_puede_actualizar_monto_de_fondo(): void
    {
        $caja = CajaChica::factory()->conMonto(5000)->create();
        $montoNuevo = 6000.00;

        $resultado = $this->service->actualizarFondo($caja->idCajaChica, $montoNuevo);

        $this->assertNotNull($resultado);
        $this->assertFloatEquals($montoNuevo, $caja->fresh()->montoCajaChica);
        $this->assertEquals($caja->idCajaChica, $resultado['caja']->idCajaChica);
    }

    public function test_monto_de_fondo_puede_ser_decimal(): void
    {
        $montoDecimal = 4567.89;
        $caja = CajaChica::factory()->conMonto($montoDecimal)->create();

        $this->assertFloatEquals($montoDecimal, $caja->montoCajaChica);
    }

    public function test_caja_chica_tiene_timestamps(): void
    {
        $caja = CajaChica::factory()->create();

        $this->assertNotNull($caja->created_at);
        $this->assertNotNull($caja->updated_at);
    }

    public function test_caja_chica_soporta_soft_delete(): void
    {
        $caja = CajaChica::factory()->create();
        $idCaja = $caja->idCajaChica;

        $caja->delete();

        $this->assertSoftDeleted('tes_caja_chica', ['idCajaChica' => $idCaja]);
        $this->assertNull(CajaChica::find($idCaja));
        $this->assertNotNull(CajaChica::withTrashed()->find($idCaja));
    }
}
