<?php

namespace App\Events\Tesoreria;

use App\Models\Tesoreria\TesCfe;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CfeEliminado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TesCfe $cfe,
    ) {}
}
