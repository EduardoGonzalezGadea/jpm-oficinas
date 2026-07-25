<?php

namespace App\Listeners\Tesoreria;

use App\Events\Tesoreria\CfeCreado;
use App\Events\Tesoreria\CfeEliminado;
use App\Services\Tesoreria\RegistrarAsientosCfeService;
use Illuminate\Support\Facades\Log;

class RegistrarAsientosCfeListener
{
    public function __construct(
        private readonly RegistrarAsientosCfeService $service,
    ) {}

    public function handleCfeCreado(CfeCreado $event): void
    {
        try {
            $this->service->registrarAsientosPorCfeCreado(
                $event->cfe,
                $event->items,
                $event->mediosPago,
            );
        } catch (\Throwable $e) {
            Log::error('RegistrarAsientosCfeListener: error al crear asientos por CFE creado', [
                'cfe_id' => $event->cfe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function handleCfeEliminado(CfeEliminado $event): void
    {
        try {
            $this->service->registrarContraAsientosPorCfeEliminado($event->cfe);
        } catch (\Throwable $e) {
            Log::error('RegistrarAsientosCfeListener: error al crear contra-asientos por CFE eliminado', [
                'cfe_id' => $event->cfe->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events): void
    {
        $events->listen(CfeCreado::class, [self::class, 'handleCfeCreado']);
        $events->listen(CfeEliminado::class, [self::class, 'handleCfeEliminado']);
    }
}
