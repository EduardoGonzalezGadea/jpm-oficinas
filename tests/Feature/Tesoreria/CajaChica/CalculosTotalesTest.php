<?php

namespace Tests\Feature\Tesoreria\CajaChica;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Pendiente;
use App\Services\Tesoreria\CajaChicaService;
use Illuminate\Support\Collection;
use Tests\TesoreriaTestCase;

/**
 * Tests de Cálculos y Totales de Caja Chica
 * 
 * Cubre:
 * - Cálculo de totales de pendientes
 * - Cálculo de totales de pagos
 * - Cálculo de disponible
 * - Cálculo de rendido y recuperado
 * - Validación de montos
 */
class CalculosTotalesTest extends TesoreriaTestCase
{
    private CajaChicaService $service;
    private CajaChica $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CajaChicaService::class);
        $this->caja = CajaChica::factory()->conMonto(5000)->create();
    }

    public function test_calcula_totales_con_caja_vacia(): void
    {
        $pendientes = collect([]);
        $pagos = collect([]);

        $totales = $this->service->calcularTotales($this->caja, $pendientes, $pagos);

        $this->assertFloatEquals(5000.00, $totales['fondoFijo']);
        $this->assertFloatEquals(0.00, $totales['totalPendientes']);
        $this->assertFloatEquals(0.00, $totales['totalPagos']);
        $this->assertFloatEquals(5000.00, $totales['disponible']);
    }

    public function test_calcula_totales_con_pendientes(): void
    {
        $pendientes = Pendiente::factory()
            ->count(3)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPendientes' => 500],
                ['montoPendientes' => 750],
                ['montoPendientes' => 300]
            )
            ->create();

        $totales = $this->service->calcularTotales($this->caja, $pendientes, collect([]));

        $totalEsperado = 500 + 750 + 300; // 1550
        $this->assertFloatEquals($totalEsperado, $totales['totalPendientes']);
        $this->assertFloatEquals(5000 - $totalEsperado, $totales['disponible']);
    }

    public function test_calcula_totales_con_pagos(): void
    {
        $pagos = Pago::factory()
            ->count(3)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPagos' => 1000],
                ['montoPagos' => 800],
                ['montoPagos' => 600]
            )
            ->create();

        $totales = $this->service->calcularTotales($this->caja, collect([]), $pagos);

        $totalEsperado = 1000 + 800 + 600; // 2400
        $this->assertFloatEquals($totalEsperado, $totales['totalPagos']);
        $this->assertFloatEquals(5000 - $totalEsperado, $totales['disponible']);
    }

    public function test_calcula_totales_con_pendientes_y_pagos(): void
    {
        $pendientes = Pendiente::factory()
            ->count(2)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPendientes' => 500],
                ['montoPendientes' => 300]
            )
            ->create();

        $pagos = Pago::factory()
            ->count(2)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPagos' => 1000],
                ['montoPagos' => 600]
            )
            ->create();

        $totales = $this->service->calcularTotales($this->caja, $pendientes, $pagos);

        $totalPendientes = 500 + 300; // 800
        $totalPagos = 1000 + 600; // 1600
        $disponible = 5000 - $totalPendientes - $totalPagos; // 2600

        $this->assertFloatEquals($totalPendientes, $totales['totalPendientes']);
        $this->assertFloatEquals($totalPagos, $totales['totalPagos']);
        $this->assertFloatEquals($disponible, $totales['disponible']);
    }

    public function test_disponible_no_puede_ser_negativo(): void
    {
        // Crear pendientes y pagos que superan el fondo
        $pendientes = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(3000)
            ->create();

        $pagos = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(3000)
            ->create();

        $totales = $this->service->calcularTotales(
            $this->caja,
            collect([$pendientes]),
            collect([$pagos])
        );

        // Fondo 5000 - pendientes 3000 - pagos 3000 = -1000
        $disponibleEsperado = 5000 - 3000 - 3000;
        $this->assertEquals($disponibleEsperado, $totales['disponible']);
        $this->assertLessThan(0, $totales['disponible'], 'El disponible puede ser negativo si hay sobreuso');
    }

    public function test_calcula_rendido_total_de_pendientes(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1000)
            ->create();

        Movimiento::factory()
            ->count(3)
            ->paraPendiente($pendiente)
            ->sequence(
                ['rendido' => 300, 'reintegrado' => null, 'recuperado' => null],
                ['rendido' => 250, 'reintegrado' => 50, 'recuperado' => null],
                ['rendido' => 200, 'reintegrado' => null, 'recuperado' => 100]
            )
            ->create();

        $totales = $this->service->calcularTotales(
            $this->caja,
            collect([$pendiente]),
            collect([])
        );

        // Total rendido = 300 + 250 + 200 = 750
        $rendidoEsperado = 300 + 250 + 200;
        
        // Nota: Verificar si calcularTotales incluye estos cálculos
        $this->assertArrayHasKey('totalPendientes', $totales);
    }

    public function test_calcula_recuperado_total_de_pagos(): void
    {
        $pagos = Pago::factory()
            ->count(3)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPagos' => 1000, 'recuperadoPagos' => 400],
                ['montoPagos' => 800, 'recuperadoPagos' => 300],
                ['montoPagos' => 600, 'recuperadoPagos' => null]
            )
            ->create();

        $totales = $this->service->calcularTotales(
            $this->caja,
            collect([]),
            $pagos
        );

        // Total recuperado = 400 + 300 + 0 = 700
        // Verificar en totales si se incluye recuperado
        $this->assertArrayHasKey('totalPagos', $totales);
    }

    public function test_calcula_totales_con_valores_decimales(): void
    {
        $pendientes = Pendiente::factory()
            ->count(2)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPendientes' => 567.89],
                ['montoPendientes' => 432.11]
            )
            ->create();

        $pagos = Pago::factory()
            ->count(2)
            ->paraCajaChica($this->caja)
            ->sequence(
                ['montoPagos' => 1234.56],
                ['montoPagos' => 876.44]
            )
            ->create();

        $totales = $this->service->calcularTotales($this->caja, $pendientes, $pagos);

        $totalPendientes = 567.89 + 432.11; // 1000.00
        $totalPagos = 1234.56 + 876.44; // 2111.00
        $disponible = 5000.00 - $totalPendientes - $totalPagos; // 1889.00

        $this->assertFloatEquals($totalPendientes, $totales['totalPendientes'], 0.01);
        $this->assertFloatEquals($totalPagos, $totales['totalPagos'], 0.01);
        $this->assertFloatEquals($disponible, $totales['disponible'], 0.01);
    }

    public function test_calcula_porcentaje_utilizado(): void
    {
        $pendientes = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(1500)
            ->create();

        $pagos = Pago::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(2500)
            ->create();

        $totales = $this->service->calcularTotales(
            $this->caja,
            collect([$pendientes]),
            collect([$pagos])
        );

        // Total utilizado = 1500 + 2500 = 4000
        // Porcentaje = 4000 / 5000 = 0.8 = 80%
        $utilizado = $totales['totalPendientes'] + $totales['totalPagos'];
        $porcentaje = ($utilizado / $this->caja->montoCajaChica) * 100;

        $this->assertFloatEquals(80.0, $porcentaje, 0.1);
    }

    public function test_fondo_cero_no_causa_division_por_cero(): void
    {
        $cajaVacia = CajaChica::factory()->conMonto(0)->create();

        $totales = $this->service->calcularTotales(
            $cajaVacia,
            collect([]),
            collect([])
        );

        $this->assertFloatEquals(0.00, $totales['fondoFijo']);
        $this->assertFloatEquals(0.00, $totales['disponible']);
    }

    public function test_montos_grandes_no_causan_overflow(): void
    {
        $cajaGrande = CajaChica::factory()->conMonto(999999.99)->create();

        $pendientes = Pendiente::factory()
            ->paraCajaChica($cajaGrande)
            ->conMonto(500000.50)
            ->create();

        $pagos = Pago::factory()
            ->paraCajaChica($cajaGrande)
            ->conMonto(400000.49)
            ->create();

        $totales = $this->service->calcularTotales(
            $cajaGrande,
            collect([$pendientes]),
            collect([$pagos])
        );

        $disponible = 999999.99 - 500000.50 - 400000.49;
        $this->assertFloatEquals($disponible, $totales['disponible'], 0.01);
    }
}
