<?php

namespace App\Listeners\Tesoreria;

use App\Events\Tesoreria\CfeProcesado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CfeProcesadoListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CfeProcesado $event): void
    {
        Log::channel('cfe_processing')->info('CFE procesado', [
            'pendiente_id' => $event->pendiente->id,
            'tipo_cfe' => $event->tipoCfe,
            'duracion_ms' => $event->duracionMs,
            'user_id' => $event->pendiente->user_id,
        ]);
    }
}