<?php

namespace Tests\Feature\Tesoreria\CajaChica;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Dependencia;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pendiente;
use App\Services\Tesoreria\CajaChicaService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Pendientes de Caja Chica
 * 
 * Cubre:
 * - Creación de pendientes
 * - Movimientos de pendientes
 * - Rendición de pendientes
 * - Recuperación de pendientes
 * - Cálculos de saldos
 */
class PendientesTest extends TesoreriaTestCase
{
    private CajaChicaService $service;
    private CajaChica $caja;
    private Dependencia $dependencia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CajaChicaService::class);
        $this->caja = CajaChica::factory()->conMonto(5000)->create();
        $this->dependencia = Dependencia::factory()->create();
    }

    public function test_puede_crear_pendiente(): void
    {
        $data = [
            'relCajaChica' => $this->caja->idCajaChica,
            'pendiente' => 'PEND-001',
            'fechaPendientes' => '2026-08-10',
            'relDependencia' => $this->dependencia->idDependencias,
            'montoPendientes' => 500.00,
        ];

        $pendiente = $this->service->crearPendiente($data);

        $this->assertNotNull($pendiente);
        $this->assertPendienteCreado([
            'relCajaChica' => $this->caja->idCajaChica,
            'montoPendientes' => 500.00,
        ]);
    }

    public function test_pendiente_con_factory(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->paraDependencia($this->dependencia)
            ->conMonto(750.50)
            ->create();

        $this->assertEquals($this->caja->idCajaChica, $pendiente->relCajaChica);
        $this->assertEquals($this->dependencia->idDependencias, $pendiente->relDependencia);
        $this->assertFloatEquals(750.50, $pendiente->montoPendientes);
    }

    public function test_puede_crear_multiples_pendientes_para_misma_caja(): void
    {
        $pendientes = Pendiente::factory()
            ->count(5)
            ->paraCajaChica($this->caja)
            ->create();

        $this->assertCount(5, $this->caja->fresh()->pendientes);
    }

    public function test_pendiente_tiene_relacion_con_caja_chica(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $this->assertNotNull($pendiente->cajaChica);
        $this->assertEquals($this->caja->idCajaChica, $pendiente->cajaChica->idCajaChica);
    }

    public function test_pendiente_tiene_relacion_con_dependencia(): void
    {
        $pendiente = Pendiente::factory()
            ->paraDependencia($this->dependencia)
            ->create();

        $this->assertNotNull($pendiente->dependencia);
        $this->assertEquals($this->dependencia->idDependencias, $pendiente->dependencia->idDependencias);
    }

    public function test_puede_crear_movimiento_para_pendiente(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $data = [
            'relPendiente' => $pendiente->idPendientes,
            'fechaMovimientos' => '2026-08-15',
            'documentos' => 'DOC-001',
            'rendido' => null,
            'reintegrado' => null,
            'recuperado' => null,
        ];

        $movimiento = $this->service->crearMovimiento($data);

        $this->assertNotNull($movimiento);
        $this->assertEquals($pendiente->idPendientes, $movimiento->relPendiente);
        $this->assertEquals('DOC-001', $movimiento->documentos);
    }

    public function test_movimiento_con_factory(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->enFecha('2026-08-10')
            ->create();

        $this->assertEquals($pendiente->idPendientes, $movimiento->relPendiente);
        $this->assertNotNull($movimiento->pendiente);
    }

    public function test_movimiento_normaliza_documentos_a_mayusculas(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->create(['documentos' => 'doc-001']);

        $this->assertEquals('DOC-001', $movimiento->documentos);
    }

    public function test_puede_rendir_movimiento(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(500)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->rendido(450.00)
            ->create();

        $this->assertFloatEquals(450.00, $movimiento->rendido);
    }

    public function test_puede_reintegrar_movimiento(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(500)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->rendido(450.00)
            ->reintegrado(50.00)
            ->create();

        $this->assertFloatEquals(450.00, $movimiento->rendido);
        $this->assertFloatEquals(50.00, $movimiento->reintegrado);
    }

    public function test_puede_recuperar_movimiento(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(500)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->rendido(450.00)
            ->recuperado(200.00)
            ->create();

        $this->assertFloatEquals(450.00, $movimiento->rendido);
        $this->assertFloatEquals(200.00, $movimiento->recuperado);
    }

    public function test_pendiente_puede_tener_multiples_movimientos(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        Movimiento::factory()
            ->count(3)
            ->paraPendiente($pendiente)
            ->create();

        $this->assertCount(3, $pendiente->fresh()->movimientos);
    }

    public function test_puede_actualizar_pendiente(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(500)
            ->create();

        $pendienteActualizado = $this->service->actualizarPendiente($pendiente->idPendientes, [
            'relCajaChica' => $this->caja->idCajaChica,
            'pendiente' => 'PEND-NUEVO',
            'fechaPendientes' => '2026-08-20',
            'relDependencia' => $this->dependencia->idDependencias,
            'montoPendientes' => 750.00,
        ]);

        $this->assertEquals('PEND-NUEVO', $pendienteActualizado->pendiente);
        $this->assertFloatEquals(750.00, $pendienteActualizado->montoPendientes);
    }

    public function test_puede_actualizar_movimiento(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->create();

        $movimientoActualizado = $this->service->actualizarMovimiento($movimiento->idMovimientos, [
            'relPendiente' => $pendiente->idPendientes,
            'fechaMovimientos' => '2026-08-25',
            'documentos' => 'DOC-NUEVO',
            'rendido' => 400.00,
            'reintegrado' => 50.00,
            'recuperado' => 100.00,
        ]);

        $this->assertEquals('DOC-NUEVO', $movimientoActualizado->documentos);
        $this->assertFloatEquals(400.00, $movimientoActualizado->rendido);
    }

    public function test_puede_eliminar_pendiente(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $idPendiente = $pendiente->idPendientes;

        $this->service->eliminarPendiente($idPendiente);

        $this->assertSoftDeleted('tes_cch_pendientes', ['idPendientes' => $idPendiente]);
    }

    public function test_puede_eliminar_movimiento(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->create();

        $movimiento = Movimiento::factory()
            ->paraPendiente($pendiente)
            ->create();

        $idMovimiento = $movimiento->idMovimientos;

        $this->service->eliminarMovimiento($idMovimiento);

        $this->assertSoftDeleted('tes_cch_movimientos', ['idMovimientos' => $idMovimiento]);
    }

    public function test_puede_calcular_monto_recuperable_rendido(): void
    {
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->conMonto(500)
            ->enFecha('2026-08-01')
            ->create();

        // Crear movimientos rendidos
        Movimiento::factory()
            ->paraPendiente($pendiente)
            ->rendido(400)
            ->recuperado(100)
            ->enFecha('2026-08-10')
            ->create();

        $fechaHasta = '2026-08-31';
        $recuperable = $this->service->calcularMontoRecuperableRendido(
            $pendiente->idPendientes,
            $fechaHasta
        );

        // Recuperable = rendido (400) - recuperado (100) = 300
        $this->assertFloatEquals(300.00, $recuperable);
    }

    public function test_pendiente_con_fecha_especifica(): void
    {
        $fecha = '2026-07-15';
        $pendiente = Pendiente::factory()
            ->paraCajaChica($this->caja)
            ->enFecha($fecha)
            ->create();

        $this->assertEquals($fecha, $pendiente->fechaPendientes->format('Y-m-d'));
    }
}
