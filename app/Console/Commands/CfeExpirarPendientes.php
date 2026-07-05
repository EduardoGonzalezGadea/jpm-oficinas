<?php

namespace App\Console\Commands;

use App\Models\TesCfePendiente;
use Illuminate\Console\Command;

class CfeExpirarPendientes extends Command
{
    protected $signature = 'cfe:expirar-pendientes {--dias=7 : Días máximos sin procesar}';

    protected $description = 'Marca como expirados los CFEs pendientes con más de N días de antigüedad';

    public function handle(): int
    {
        $dias = (int)$this->option('dias');
        $limite = now()->subDays($dias);

        $expirados = TesCfePendiente::whereIn('estado', ['pendiente', 'en_revision'])
            ->where('created_at', '<', $limite)
            ->get();

        $count = 0;
        foreach ($expirados as $pendiente) {
            $pendiente->estado = 'expirado';
            $pendiente->motivo_rechazo = "Expirado automáticamente tras {$dias} días sin procesar.";
            $pendiente->save();
            $count++;
        }

        $this->info("✓ {$count} CFEs pendientes marcados como expirados (>{$dias} días).");
        return Command::SUCCESS;
    }
}
