<?php

namespace App\Listeners\Tesoreria;

use App\Events\Tesoreria\CfeConfirmado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CfeConfirmadoListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CfeConfirmado $event): void
    {
        Log::channel('cfe_audit')->info('CFE confirmado', [
            'cfe_id' => $event->cfe->id,
            'pendiente_id' => $event->pendiente->id,
            'tipo_cfe' => $event->cfe->documento_tipo,
            'monto' => $event->cfe->total_a_pagar,
            'usuario' => $event->pendiente->procesado_por,
        ]);
    }
}