<?php

namespace App\Jobs;

use App\Models\TesCfePendiente;
use App\Services\Tesoreria\CfeConfirmationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConfirmarCfeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $pendienteId
    ) {}

    public function handle(CfeConfirmationService $confirmationService): void
    {
        $pendiente = TesCfePendiente::find($this->pendienteId);

        if (!$pendiente) {
            Log::warning('ConfirmarCfeJob: CFE pendiente no encontrado', [
                'pendiente_id' => $this->pendienteId,
            ]);
            return;
        }

        if (!in_array($pendiente->estado, ['pendiente', 'en_revision'])) {
            Log::warning('ConfirmarCfeJob: CFE no está en estado confirmable', [
                'pendiente_id' => $this->pendienteId,
                'estado' => $pendiente->estado,
            ]);
            return;
        }

        try {
            $confirmationService->confirmar($pendiente);

            Log::info('CFE auto-confirmado exitosamente', [
                'pendiente_id' => $pendiente->id,
                'tipo_cfe' => $pendiente->tipo_cfe,
            ]);
        } catch (\Exception $e) {
            Log::error('Error auto-confirmando CFE', [
                'pendiente_id' => $pendiente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar para que el job falle y se reintente
            throw $e;
        }
    }
}