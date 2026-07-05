<?php

namespace App\Jobs;

use App\Models\TesCfePendiente;
use App\Services\CfeProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCfePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $pendienteId
    ) {}

    public function handle(CfeProcessorService $processor): void
    {
        $pendiente = TesCfePendiente::find($this->pendienteId);

        if (!$pendiente) {
            Log::warning('ProcessCfePdfJob: CFE pendiente no encontrado', [
                'pendiente_id' => $this->pendienteId,
            ]);
            return;
        }

        if (!in_array($pendiente->estado, ['pendiente', 'en_proceso', 'en_revision'])) {
            Log::warning('ProcessCfePdfJob: CFE no está en estado procesable', [
                'pendiente_id' => $this->pendienteId,
                'estado'       => $pendiente->estado,
            ]);
            return;
        }

        $pendiente->estado = 'en_proceso';
        $pendiente->save();

        $processor->procesarPendienteExistente($pendiente);

        Log::info('CFE re-procesado exitosamente', [
            'pendiente_id' => $pendiente->id,
            'tipo_cfe'     => $pendiente->tipo_cfe,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $pendiente = TesCfePendiente::find($this->pendienteId);
        if ($pendiente && !in_array($pendiente->estado, ['confirmado', 'rechazado', 'procesado'])) {
            $pendiente->estado = 'error';
            $pendiente->save();
        }

        Log::error('ProcessCfePdfJob failed', [
            'pendiente_id' => $this->pendienteId,
            'error'        => $exception->getMessage(),
        ]);
    }
}
