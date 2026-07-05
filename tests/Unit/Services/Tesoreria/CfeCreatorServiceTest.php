<?php

namespace Tests\Unit\Services\Tesoreria;

use App\DataTransferObjects\CfeData;
use App\Exceptions\Tesoreria\CfeDuplicateException;
use App\Exceptions\Tesoreria\CfeValidationException;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesPlanillaEr;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\TesCfePendiente;
use App\Services\Tesoreria\CfeCreatorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CfeCreatorServiceTest extends TestCase
{
    use DatabaseTransactions;

    private CfeCreatorService $service;
    private CajaConcepto $concepto;
    private SiifDistribucionDependencia $dependencia;
    private TesPlanillaEr $planilla;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CfeCreatorService();

        $tipo = SiifDistribucionTipo::create(['tipo' => 'Test Tipo']);
        $this->dependencia = SiifDistribucionDependencia::create([
            'dependencia' => 'Test Dependencia',
            'abreviatura' => 'TEST',
        ]);
        $this->planilla = TesPlanillaEr::create([
            'fecha' => now(),
            'numero' => 'PLANILLA001',
            'tipo_id' => $tipo->id,
            'dependencia_id' => $this->dependencia->id,
        ]);
        $this->concepto = CajaConcepto::create([
            'caja_concepto' => 'Test Concepto',
            'requiere_distribucion' => false,
            'requiere_confirmacion' => false,
            'siif_distribucion_tipo_id' => $tipo->id,
        ]);
    }

    public function test_create_manual_creates_cfe_with_items_and_medios_pago(): void
    {
        $data = new CfeData(
            documento_tipo: 'E-Factura Cobranza',
            documento_serie: 'A',
            documento_numero: '123456',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test Receptor',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [
                ['detalle' => 'Item 1', 'importe' => 500],
                ['detalle' => 'Item 2', 'importe' => 500],
            ],
            medios_pago: [
                ['tipo' => 'Efectivo', 'valor' => 1000],
            ],
            moneda: 'UYU',
        );

        $cfe = $this->service->createManual($data);

        $this->assertInstanceOf(TesCfe::class, $cfe);
        $this->assertDatabaseHas('tes_cfes', ['id' => $cfe->id, 'documento_numero' => '123456']);
        $this->assertCount(2, $cfe->items);
        $this->assertCount(1, $cfe->mediosPago);
        $this->assertEquals(1000, $cfe->total_a_pagar);
    }

    public function test_create_manual_rejects_totals_mismatch(): void
    {
        $this->expectException(CfeValidationException::class);

        $data = new CfeData(
            documento_tipo: 'E-Factura Cobranza',
            documento_numero: '789012',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [
                ['detalle' => 'Item 1', 'importe' => 800],
            ],
            medios_pago: [
                ['tipo' => 'Efectivo', 'valor' => 1000],
            ],
            moneda: 'UYU',
        );

        $this->service->createManual($data);
    }

    public function test_create_manual_rejects_duplicate_document(): void
    {
        $data = new CfeData(
            documento_tipo: 'E-Factura Cobranza',
            documento_numero: 'DUPLICADO',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [['detalle' => 'Item', 'importe' => 100]],
            moneda: 'UYU',
        );
        $this->service->createManual($data);

        $this->expectException(CfeDuplicateException::class);
        $this->service->createManual($data);
    }

    public function test_create_manual_without_medios_pago_skips_totals_validation(): void
    {
        $data = new CfeData(
            documento_tipo: 'E-Ticket Cobranza',
            documento_numero: 'SINMP',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [['detalle' => 'Unico item', 'importe' => 1500]],
            moneda: 'UYU',
        );

        $cfe = $this->service->createManual($data);
        $this->assertEquals(1500, $cfe->total_a_pagar);
    }

    public function test_delete_cfe_blocks_when_item_in_planilla(): void
    {
        $cfe = TesCfe::factory()->create();
        $cfe->items()->create([
            'detalle' => 'En planilla',
            'importe' => 100,
            'planilla_er_id' => $this->planilla->id,
        ]);

        $this->expectException(CfeValidationException::class);
        $this->service->deleteCfe($cfe->id);
    }

    public function test_delete_cfe_soft_deletes(): void
    {
        $data = new CfeData(
            documento_tipo: 'E-Factura Cobranza',
            documento_numero: 'TODELETE',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [['detalle' => 'Item', 'importe' => 100]],
            moneda: 'UYU',
        );
        $cfe = $this->service->createManual($data);

        $this->service->deleteCfe($cfe->id);

        $this->assertSoftDeleted('tes_cfes', ['id' => $cfe->id]);
    }

    public function test_update_cfe_blocks_when_item_in_planilla(): void
    {
        $cfe = TesCfe::factory()->create();
        $item = $cfe->items()->create([
            'detalle' => 'En planilla',
            'importe' => 100,
            'planilla_er_id' => $this->planilla->id,
        ]);

        $data = new CfeData(
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [['id' => $item->id, 'detalle' => 'En planilla', 'importe' => 100]],
        );

        $this->expectException(CfeValidationException::class);
        $this->service->updateCfe($cfe->id, $data);
    }

    public function test_validate_totals_match_passes_with_force(): void
    {
        $this->service->validateTotalsMatch(
            [['importe' => 500]],
            [['valor' => 1000]],
            true
        );

        $this->assertTrue(true);
    }

    public function test_check_duplicate_rejects_same_serie_numero_in_tes_cfe(): void
    {
        TesCfe::factory()->create([
            'documento_tipo' => 'E-Factura Cobranza',
            'documento_serie' => 'X',
            'documento_numero' => 'DDUP001',
        ]);

        $this->expectException(CfeDuplicateException::class);
        $this->expectExceptionMessage('ya existe en el sistema');

        $this->service->checkDocumentoDuplicado('E-Factura Cobranza', 'DDUP001', 'X');
    }

    public function test_check_duplicate_allows_same_numero_different_serie_in_tes_cfe(): void
    {
        TesCfe::factory()->create([
            'documento_tipo' => 'E-Factura Cobranza',
            'documento_serie' => 'A',
            'documento_numero' => '123456',
        ]);

        $this->service->checkDocumentoDuplicado('E-Factura Cobranza', '123456', 'B');
        $this->assertTrue(true);
    }

    private function crearPendiente(array $overrides = []): TesCfePendiente
    {
        $defaults = [
            'tipo_cfe' => 'multas_cobradas',
            'serie' => 'P',
            'numero' => 'PEND001',
            'fecha' => now(),
            'monto' => 1000,
            'moneda' => 'UYU',
            'datos_extraidos' => '{}',
            'estado' => 'pendiente',
        ];
        return TesCfePendiente::create(array_merge($defaults, $overrides));
    }

    public function test_check_duplicate_rejects_pendiente_with_same_serie_numero(): void
    {
        $this->crearPendiente(['serie' => 'P', 'numero' => 'PEND001']);

        $this->expectException(CfeDuplicateException::class);
        $this->expectExceptionMessage('ya existe como pendiente');

        $this->service->checkDocumentoDuplicado('E-Factura Cobranza', 'PEND001', 'P');
    }

    public function test_check_duplicate_ignores_rechazado_pendiente(): void
    {
        $this->crearPendiente([
            'serie' => 'R',
            'numero' => 'RECHAZ01',
            'tipo_cfe' => 'multas_cobradas',
            'monto' => 500,
            'estado' => 'rechazado',
        ]);

        $this->service->checkDocumentoDuplicado('E-Factura Cobranza', 'RECHAZ01', 'R');
        $this->assertTrue(true);
    }

    public function test_create_manual_rejects_when_pendiente_exists(): void
    {
        $this->crearPendiente([
            'serie' => 'A',
            'numero' => 'PENDFLOW',
            'monto' => 2000,
        ]);

        $this->expectException(CfeDuplicateException::class);
        $this->expectExceptionMessage('ya existe como pendiente');

        $data = new CfeData(
            documento_tipo: 'E-Factura Cobranza',
            documento_serie: 'A',
            documento_numero: 'PENDFLOW',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [['detalle' => 'Item', 'importe' => 2000]],
            moneda: 'UYU',
        );

        $this->service->createManual($data);
    }

    public function test_check_duplicate_detects_same_tipo_serie_numero_without_serie(): void
    {
        TesCfe::factory()->create([
            'documento_tipo' => 'e-Ticket',
            'documento_serie' => null,
            'documento_numero' => '12345',
        ]);

        $this->expectException(CfeDuplicateException::class);
        $this->expectExceptionMessage('ya existe en el sistema');

        $this->service->checkDocumentoDuplicado('e-Ticket', '12345', null);
    }

    public function test_check_duplicate_allows_same_numero_different_serie_or_tipo(): void
    {
        TesCfe::factory()->create([
            'documento_tipo' => 'e-Factura',
            'documento_serie' => 'A',
            'documento_numero' => '99999',
        ]);

        // Mismo numero, distinta serie -> no debe rechazar
        $this->service->checkDocumentoDuplicado('e-Factura', '99999', 'B');
        $this->assertTrue(true);
    }

    public function test_check_duplicate_rejects_same_numero_and_null_serie_in_pendiente(): void
    {
        $this->crearPendiente([
            'serie' => null,
            'numero' => 'NOSERIE01',
            'monto' => 500,
        ]);

        $this->expectException(CfeDuplicateException::class);
        $this->expectExceptionMessage('ya existe como pendiente');

        $this->service->checkDocumentoDuplicado('e-Ticket', 'NOSERIE01', null);
    }

    public function test_check_duplicate_allows_different_tipo_with_same_numero_in_tes_cfe(): void
    {
        TesCfe::factory()->create([
            'documento_tipo' => 'e-Factura',
            'documento_serie' => null,
            'documento_numero' => '12345',
        ]);

        // Mismo numero y null serie, pero distinto tipo -> documentos diferentes
        $this->service->checkDocumentoDuplicado('e-Ticket', '12345', null);
        $this->assertTrue(true);
    }

    public function test_create_manual_rejects_when_pendiente_exists_without_serie(): void
    {
        $this->crearPendiente([
            'serie' => null,
            'numero' => 'NOSERIECFE',
            'monto' => 3000,
        ]);

        $this->expectException(CfeDuplicateException::class);
        $this->expectExceptionMessage('ya existe como pendiente');

        $data = new CfeData(
            documento_tipo: 'e-Ticket',
            documento_numero: 'NOSERIECFE',
            fecha: now()->format('Y-m-d'),
            receptor_nombre_denominacion: 'Test',
            tes_caja_concepto_id: $this->concepto->id,
            siif_distribucion_dependencia_id: $this->dependencia->id,
            items: [['detalle' => 'Item', 'importe' => 3000]],
            moneda: 'UYU',
        );

        $this->service->createManual($data);
    }
}
