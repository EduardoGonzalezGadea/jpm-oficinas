<?php

namespace App\Events\Tesoreria;

use App\Models\Tesoreria\TesCfe;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CfeActualizado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TesCfe $cfe,
        public array $changes = [],
    ) {}
}
