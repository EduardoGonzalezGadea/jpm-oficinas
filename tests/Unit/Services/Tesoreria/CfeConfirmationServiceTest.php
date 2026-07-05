<?php

namespace Tests\Unit\Services\Tesoreria;

use App\Models\TesCfePendiente;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\TesCfe;
use App\Services\Tesoreria\CfeConfirmationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CfeConfirmationServiceTest extends TestCase
{
    use DatabaseTransactions;

    private CfeConfirmationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CfeConfirmationService::class);
    }

    public function test_rechazar_cfe_pendiente_marca_como_rechazado(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => ['tipo_cfe' => 'multas_cobradas'],
            'estado'          => 'pendiente',
        ]);

        $this->service->rechazar($pendiente, 'Documento duplicado');

        $this->assertEquals('rechazado', $pendiente->fresh()->estado);
        $this->assertEquals('Documento duplicado', $pendiente->fresh()->motivo_rechazo);
    }

    public function test_marcar_en_revision_cambia_estado(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $this->service->marcarEnRevision($pendiente);

        $this->assertEquals('en_revision', $pendiente->fresh()->estado);
    }

    public function test_confirmar_crea_tes_cfe_desde_pendiente(): void
    {
        $tipo = SiifDistribucionTipo::create(['tipo' => 'Test']);
        $dep = SiifDistribucionDependencia::create(['dependencia' => 'Test Dep', 'abreviatura' => 'TD']);
        $concepto = CajaConcepto::create([
            'caja_concepto' => 'Multas de Tránsito',
            'siif_distribucion_tipo_id' => $tipo->id,
            'requiere_distribucion' => false,
        ]);
        $depId = $dep->id;

        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'serie'           => 'A',
            'numero'          => '12345',
            'monto'           => 1000.00,
            'estado'          => 'pendiente',
            'datos_extraidos' => [
                'tipo_cfe' => 'e-Ticket',
                'serie' => 'A',
                'numero' => '12345',
                'fecha' => '2026-06-01',
                'nombre' => 'Juan Perez',
                'cedula' => '12345678',
                'items' => [
                    ['detalle' => 'Multa XXX', 'cantidad' => 1, 'precio' => 1000, 'importe' => 1000],
                ],
                'referencias' => 'e-Factura A-999',
                'adenda' => 'Test',
                'siif_distribucion_dependencia_id' => $depId,
            ],
            'pdf_path' => 'test.pdf',
        ]);

        $cfe = $this->service->confirmar($pendiente);

        $this->assertInstanceOf(TesCfe::class, $cfe);
        $this->assertEquals('e-Ticket', $cfe->documento_tipo);
        $this->assertEquals('12345', $cfe->documento_numero);
        $this->assertEquals(1000.00, (float) $cfe->total_a_pagar);
        $this->assertEquals($concepto->id, $cfe->tes_caja_concepto_id);

        $this->assertEquals('confirmado', $pendiente->fresh()->estado);
    }

    public function test_confirmar_falla_si_pendiente_no_esta_confirmable(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'rechazado',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no está en estado confirmable');

        $this->service->confirmar($pendiente);
    }

    public function test_confirmar_con_datos_editados_fusiona_correctamente(): void
    {
        $tipo = SiifDistribucionTipo::create(['tipo' => 'Test']);
        $dep = SiifDistribucionDependencia::create(['dependencia' => 'Test Dep', 'abreviatura' => 'TD']);
        $depId = $dep->id;
        CajaConcepto::create([
            'caja_concepto' => 'Multas de Tránsito',
            'siif_distribucion_tipo_id' => $tipo->id,
            'requiere_distribucion' => false,
        ]);

        $pendiente = TesCfePendiente::create([
            'tipo_cfe'  => 'multas_cobradas',
            'serie'     => 'B',
            'numero'    => '67890',
            'monto'     => 500.00,
            'estado'    => 'pendiente',
            'datos_extraidos' => [
                'tipo_cfe' => 'e-Ticket',
                'serie' => 'B',
                'numero' => '67890',
                'fecha' => '2026-06-01',
                'nombre' => 'Original Name',
                'items' => [
                    ['detalle' => 'Item original', 'cantidad' => 1, 'precio' => 500, 'importe' => 500],
                ],
                'siif_distribucion_dependencia_id' => $depId,
            ],
            'pdf_path' => 'test.pdf',
        ]);

        $datosEditados = [
            'nombre' => 'Edited Name',
            'cedula' => '87654321',
        ];

        $cfe = $this->service->confirmar($pendiente, $datosEditados);

        $this->assertEquals('Edited Name', $cfe->receptor_nombre_denominacion);
        $this->assertEquals('87654321', $cfe->receptor_documento_ruc);
    }

    public function test_confirmar_rechaza_referencia_duplicada(): void
    {
        $tipo = SiifDistribucionTipo::create(['tipo' => 'Test']);
        $dep = SiifDistribucionDependencia::create(['dependencia' => 'Test Dep', 'abreviatura' => 'TD']);
        $depId = $dep->id;
        $concepto = CajaConcepto::create([
            'caja_concepto' => 'Multas de Tránsito',
            'siif_distribucion_tipo_id' => $tipo->id,
            'requiere_distribucion' => false,
        ]);

        TesCfe::create([
            'documento_tipo' => 'E-Factura Cobranza',
            'documento_serie' => 'C',
            'documento_numero' => '555',
            'fecha' => '2026-06-01',
            'receptor_nombre_denominacion' => 'Existente',
            'total_a_pagar' => 100,
            'tes_caja_concepto_id' => $concepto->id,
            'siif_distribucion_dependencia_id' => $depId,
            'referencias' => 'e-Factura C-555',
        ]);

        $pendiente = TesCfePendiente::create([
            'tipo_cfe'  => 'multas_cobradas',
            'serie'     => 'D',
            'numero'    => '111',
            'monto'     => 200.00,
            'estado'    => 'pendiente',
            'datos_extraidos' => [
                'tipo_cfe' => 'e-Ticket',
                'serie' => 'D',
                'numero' => '111',
                'fecha' => '2026-06-01',
                'nombre' => 'Test',
                'items' => [
                    ['detalle' => 'Item', 'cantidad' => 1, 'precio' => 200, 'importe' => 200],
                ],
                'referencias' => 'e-Factura C-555',
                'siif_distribucion_dependencia_id' => $depId,
            ],
            'pdf_path' => 'test.pdf',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ya existe en el CFE');

        $this->service->confirmar($pendiente);
    }
}
