<?php

namespace App\Listeners\Tesoreria;

use App\Events\Tesoreria\CfeRechazado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CfeRechazadoListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CfeRechazado $event): void
    {
        Log::channel('cfe_audit')->warning('CFE rechazado', [
            'pendiente_id' => $event->pendiente->id,
            'motivo' => $event->pendiente->motivo_rechazo,
            'usuario' => $event->pendiente->procesado_por,
        ]);
    }
}