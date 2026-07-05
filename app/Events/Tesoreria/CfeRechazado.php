<?php

namespace App\Events\Tesoreria;

use App\Models\TesCfePendiente;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CfeRechazado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TesCfePendiente $pendiente
    ) {}
}