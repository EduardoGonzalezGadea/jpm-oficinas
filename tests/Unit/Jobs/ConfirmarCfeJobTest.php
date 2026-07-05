<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ConfirmarCfeJob;
use App\Models\TesCfePendiente;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\TesCfe;
use App\Services\Tesoreria\CfeConfirmationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConfirmarCfeJobTest extends TestCase
{
    use DatabaseTransactions;

    private int $depId;

    protected function setUp(): void
    {
        parent::setUp();
        $tipo = SiifDistribucionTipo::create(['tipo' => 'Test']);
        $this->depId = SiifDistribucionDependencia::create(['dependencia' => 'Test Dep', 'abreviatura' => 'TD'])->id;
        CajaConcepto::create([
            'caja_concepto' => 'Multas de Tránsito',
            'siif_distribucion_tipo_id' => $tipo->id,
            'requiere_distribucion' => false,
        ]);
    }

    public function test_handle_skips_when_pendiente_not_found(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'no encontrado'));

        $job = new ConfirmarCfeJob(9999);
        $job->handle(app(CfeConfirmationService::class));
    }

    public function test_handle_skips_when_pendiente_in_wrong_state(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'no está en estado confirmable'));

        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'rechazado',
        ]);

        $job = new ConfirmarCfeJob($pendiente->id);
        $job->handle(app(CfeConfirmationService::class));
    }

    public function test_handle_fails_when_confirmation_throws(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $this->expectException(\Throwable::class);

        $job = new ConfirmarCfeJob($pendiente->id);
        $job->handle(app(CfeConfirmationService::class));
    }

    public function test_handle_confirms_pendiente_successfully(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'  => 'multas_cobradas',
            'serie'     => 'A',
            'numero'    => '99999',
            'monto'     => 1500.00,
            'estado'    => 'pendiente',
            'datos_extraidos' => [
                'tipo_cfe' => 'e-Ticket',
                'serie' => 'A',
                'numero' => '99999',
                'fecha' => '2026-06-01',
                'nombre' => 'Test User',
                'cedula' => '11111111',
                'items' => [
                    ['detalle' => 'Item test', 'cantidad' => 1, 'precio' => 1500, 'importe' => 1500],
                ],
                'siif_distribucion_dependencia_id' => $this->depId,
            ],
            'pdf_path' => 'test.pdf',
        ]);

        $job = new ConfirmarCfeJob($pendiente->id);
        $job->handle(app(CfeConfirmationService::class));

        $this->assertEquals('confirmado', $pendiente->fresh()->estado);

        $cfe = TesCfe::where('documento_numero', '99999')->first();
        $this->assertNotNull($cfe);
        $this->assertEquals(1500.00, (float) $cfe->total_a_pagar);
    }
}
