<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessCfePdfJob;
use App\Models\TesCfePendiente;
use App\Services\CfeProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessCfePdfJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function handle_skips_when_pending_not_found(): void
    {
        $job = new ProcessCfePdfJob(999);

        $processor = $this->createMock(CfeProcessorService::class);
        $processor->expects($this->never())->method('procesarPendienteExistente');

        $job->handle($processor);

        $this->assertTrue(true);
    }

    /** @test */
    public function handle_skips_when_pending_in_wrong_state(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'confirmado',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);

        $processor = $this->createMock(CfeProcessorService::class);
        $processor->expects($this->never())->method('procesarPendienteExistente');

        $job->handle($processor);
    }

    /** @test */
    public function handle_skips_when_pending_is_procesado(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'procesado',
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);

        $processor = $this->createMock(CfeProcessorService::class);
        $processor->expects($this->never())->method('procesarPendienteExistente');

        $job->handle($processor);
    }

    /** @test */
    public function handle_skips_when_pending_is_rechazado(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'rechazado',
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);

        $processor = $this->createMock(CfeProcessorService::class);
        $processor->expects($this->never())->method('procesarPendienteExistente');

        $job->handle($processor);
    }

    /** @test */
    public function handle_marks_as_en_proceso_and_calls_processor(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
            'pdf_path'        => 'cfe-pendientes/test.pdf',
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);

        $processor = $this->createMock(CfeProcessorService::class);
        $processor->expects($this->once())
            ->method('procesarPendienteExistente')
            ->with($this->callback(function (TesCfePendiente $p) use ($pendiente) {
                return $p->id === $pendiente->id;
            }));

        $job->handle($processor);

        $this->assertDatabaseHas('tes_cfe_pendientes', [
            'id'     => $pendiente->id,
            'estado' => 'en_proceso',
        ]);
    }

    /** @test */
    public function failed_marks_pending_as_error(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'en_proceso',
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);
        $job->failed(new \RuntimeException('Test error'));

        $this->assertDatabaseHas('tes_cfe_pendientes', [
            'id'     => $pendiente->id,
            'estado' => 'error',
        ]);
    }

    /** @test */
    public function failed_does_not_overwrite_confirmado(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'confirmado',
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);
        $job->failed(new \RuntimeException('Test error'));

        $this->assertDatabaseHas('tes_cfe_pendientes', [
            'id'     => $pendiente->id,
            'estado' => 'confirmado',
        ]);
    }

    /** @test */
    public function failed_does_not_overwrite_rechazado(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'rechazado',
        ]);

        $job = new ProcessCfePdfJob($pendiente->id);
        $job->failed(new \RuntimeException('Test error'));

        $this->assertDatabaseHas('tes_cfe_pendientes', [
            'id'     => $pendiente->id,
            'estado' => 'rechazado',
        ]);
    }
}
