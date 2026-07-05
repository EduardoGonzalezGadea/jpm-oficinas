<?php

namespace App\Events\Tesoreria;

use App\Models\Tesoreria\TesCfe;
use App\Models\TesCfePendiente;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CfeConfirmado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TesCfe $cfe,
        public readonly TesCfePendiente $pendiente
    ) {}
}