<?php

namespace App\Listeners\Tesoreria;

use App\Events\Tesoreria\CfeActualizado;
use App\Events\Tesoreria\CfeCreado;
use App\Events\Tesoreria\CfeEliminado;

class LogCfeActivity
{
    public function handleCfeCreado(CfeCreado $event): void
    {
        activity('cfes')
            ->performedOn($event->cfe)
            ->causedBy(auth()->user())
            ->withProperties([
                'items' => $event->items,
                'medios_pago' => $event->mediosPago,
            ])
            ->log("Se creó el CFE {$event->cfe->documento_serie}-{$event->cfe->documento_numero}");
    }

    public function handleCfeActualizado(CfeActualizado $event): void
    {
        activity('cfes')
            ->performedOn($event->cfe)
            ->causedBy(auth()->user())
            ->withProperties($event->changes)
            ->log("Se actualizó el CFE {$event->cfe->documento_serie}-{$event->cfe->documento_numero}");
    }

    public function handleCfeEliminado(CfeEliminado $event): void
    {
        activity('cfes')
            ->performedOn($event->cfe)
            ->causedBy(auth()->user())
            ->log("Se eliminó el CFE {$event->cfe->documento_serie}-{$event->cfe->documento_numero}");
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events): void
    {
        $events->listen(CfeCreado::class, [self::class, 'handleCfeCreado']);
        $events->listen(CfeActualizado::class, [self::class, 'handleCfeActualizado']);
        $events->listen(CfeEliminado::class, [self::class, 'handleCfeEliminado']);
    }
}
