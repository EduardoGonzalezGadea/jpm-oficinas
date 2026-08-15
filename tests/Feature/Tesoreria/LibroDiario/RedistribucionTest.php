<?php

namespace Tests\Feature\Tesoreria\LibroDiario;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Redistribución del Libro Diario
 * 
 * Cubre:
 * - Creación de redistribuciones
 * - Asientos de origen y destino
 * - Grupos de redistribución
 * - Cálculos de saldos post-redistribución
 */
class RedistribucionTest extends TesoreriaTestCase
{
    private LibroDiarioService $service;
    private MedioDePago $medio;
    private LbConcepto $concepto;
    private LbDetalle $fondoFijo;
    private LbDetalle $pendiente;
    private LbDetalle $pagos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LibroDiarioService::class);
        
        $this->medio = $this->getMedioDePago('EF');
        $this->concepto = $this->getConcepto(LbConcepto::CAJA_CHICA);
        $this->fondoFijo = $this->getDetalle(LbDetalle::FONDO_FIJO);
        $this->pendiente = $this->getDetalle(LbDetalle::PENDIENTE);
        $this->pagos = $this->getDetalle(LbDetalle::PAGOS);
    }

    public function test_puede_registrar_redistribucion(): void
    {
        // Primero crear fondo fijo con saldo
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $origen = [
            'fecha' => '2026-08-10',
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000.00,
        ];

        $destino = [
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->pendiente->id,
            'medio_id' => $this->medio->id,
        ];

        $resultado = $this->service->registrarRedistribucion($origen, $destino);

        $this->assertArrayHasKey('asientoOrigen', $resultado);
        $this->assertArrayHasKey('asientoDestino', $resultado);
        $this->assertFloatEquals(1000.00, $resultado['asientoOrigen']->monto);
        $this->assertFloatEquals(1000.00, $resultado['asientoDestino']->monto);
    }

    public function test_redistribucion_crea_grupo(): void
    {
        // Crear fondo
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $resultado = $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->fondoFijo->id,
                'medio_id' => $this->medio->id,
                'monto' => 1000.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->pendiente->id,
                'medio_id' => $this->medio->id,
            ]
        );

        $grupoId = $resultado['asientoOrigen']->grupo_redistribucion_id;

        $this->assertNotNull($grupoId);
        $this->assertEquals($grupoId, $resultado['asientoDestino']->grupo_redistribucion_id);
    }

    public function test_redistribucion_con_factory(): void
    {
        $grupoId = LibroDiario::generarGrupoRedistribucionId();

        $origen = LibroDiario::factory()
            ->redistribucion($grupoId)
            ->salida()
            ->conMonto(1000)
            ->create([
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->fondoFijo->id,
            ]);

        $destino = LibroDiario::factory()
            ->redistribucion($grupoId)
            ->entrada()
            ->conMonto(1000)
            ->create([
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->pendiente->id,
            ]);

        $this->assertEquals($grupoId, $origen->grupo_redistribucion_id);
        $this->assertEquals($grupoId, $destino->grupo_redistribucion_id);
        $this->assertTrue($origen->esRedistribucion);
        $this->assertTrue($destino->esRedistribucion);
    }

    public function test_redistribucion_afecta_saldos_correctamente(): void
    {
        // Fondo Fijo: 5000
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $saldoFondoAntes = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->fondoFijo->id
        );

        // Redistribuir 1000 a Pendiente
        $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->fondoFijo->id,
                'medio_id' => $this->medio->id,
                'monto' => 1000.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->pendiente->id,
                'medio_id' => $this->medio->id,
            ]
        );

        $saldoFondoDespues = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->fondoFijo->id
        );

        $saldoPendiente = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->pendiente->id
        );

        // Fondo Fijo: 5000 - 1000 = 4000
        $this->assertFloatEquals(5000.00, $saldoFondoAntes);
        $this->assertFloatEquals(4000.00, $saldoFondoDespues);
        // Pendiente: 0 + 1000 = 1000
        $this->assertFloatEquals(1000.00, $saldoPendiente);
    }

    public function test_puede_hacer_multiples_redistribuciones(): void
    {
        // Fondo inicial
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        // Redistribución 1: Fondo → Pendiente (1000)
        $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->fondoFijo->id,
                'medio_id' => $this->medio->id,
                'monto' => 1000.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->pendiente->id,
                'medio_id' => $this->medio->id,
            ]
        );

        // Redistribución 2: Fondo → Pagos (1500)
        $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-15',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->fondoFijo->id,
                'medio_id' => $this->medio->id,
                'monto' => 1500.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->pagos->id,
                'medio_id' => $this->medio->id,
            ]
        );

        // Verificar saldos finales
        $saldoFondo = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->fondoFijo->id
        );

        $saldoPendiente = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->pendiente->id
        );

        $saldoPagos = $this->service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->pagos->id
        );

        // Fondo: 5000 - 1000 - 1500 = 2500
        $this->assertFloatEquals(2500.00, $saldoFondo);
        $this->assertFloatEquals(1000.00, $saldoPendiente);
        $this->assertFloatEquals(1500.00, $saldoPagos);
    }

    public function test_redistribucion_entre_diferentes_subcuentas(): void
    {
        // Crear saldo en Fondo Fijo
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 3000.00,
        ]);

        // Redistribuir a Pendiente
        $resultado = $this->service->registrarRedistribucion(
            [
                'fecha' => '2026-08-10',
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->fondoFijo->id,
                'medio_id' => $this->medio->id,
                'monto' => 800.00,
            ],
            [
                'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->pendiente->id,
                'medio_id' => $this->medio->id,
            ]
        );

        $this->assertEquals($this->fondoFijo->id, $resultado['asientoOrigen']->detalle_id);
        $this->assertEquals($this->pendiente->id, $resultado['asientoDestino']->detalle_id);
        $this->assertFloatEquals(800.00, $resultado['asientoOrigen']->monto);
        $this->assertFloatEquals(800.00, $resultado['asientoDestino']->monto);
    }

    public function test_asiento_es_redistribucion_accessor(): void
    {
        $asientoNormal = LibroDiario::factory()->entrada()->create();
        $asientoRedist = LibroDiario::factory()->redistribucion()->create();

        $this->assertFalse($asientoNormal->esRedistribucion);
        $this->assertTrue($asientoRedist->esRedistribucion);
    }

    public function test_puede_listar_asientos_base_disponibles(): void
    {
        // Crear entrada (asiento base)
        $this->service->registrarAsiento([
            'fecha' => '2026-08-01',
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->fondoFijo->id,
            'medio_id' => $this->medio->id,
            'monto' => 5000.00,
        ]);

        $disponibles = $this->service->listarAsientosBaseDisponibles(
            $this->concepto->id,
            $this->fondoFijo->id,
            $this->medio->id
        );

        $this->assertNotEmpty($disponibles);
        $this->assertGreaterThan(0, $disponibles->count());
    }
}
