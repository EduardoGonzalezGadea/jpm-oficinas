<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCfePdfJob;
use App\Models\TesCfePendiente;
use Illuminate\Console\Command;

class CfeReprocessPendientes extends Command
{
    protected $signature = 'cfe:reprocess
        {--estado= : Estado específico a re-procesar (pendiente, en_proceso, en_revision, error)}
        {--id= : ID específico de pendiente a re-procesar}
        {--all : Re-procesar todos los pendientes independientemente del estado}
        {--dry-run : Solo mostrar qué pendientes se re-procesarían sin encolar jobs}';

    protected $description = 'Re-procesa CFEs pendientes con el pipeline actual de extracción';

    public function handle(): int
    {
        $query = TesCfePendiente::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif ($this->option('all')) {
            // Sin filtro de estado
        } elseif ($estado = $this->option('estado')) {
            $query->where('estado', $estado);
        } else {
            $query->whereIn('estado', ['pendiente', 'en_proceso', 'en_revision', 'error']);
        }

        $pendientes = $query->get();

        if ($pendientes->isEmpty()) {
            $this->warn('No se encontraron pendientes para re-procesar.');
            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$pendientes->count()} pendientes para re-procesar.");
        $this->newLine();

        $bar = $this->output->createProgressBar($pendientes->count());
        $bar->start();

        foreach ($pendientes as $pendiente) {
            if ($this->option('dry-run')) {
                $this->line("  [DRY-RUN] Pendiente #{$pendiente->id} ({$pendiente->tipo_cfe}) - {$pendiente->serie}-{$pendiente->numero}");
            } else {
                ProcessCfePdfJob::dispatch($pendiente->id);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->option('dry-run')) {
            $this->info('Dry-run completado. No se encolaron jobs.');
        } else {
            $this->info("✓ {$pendientes->count()} jobs encolados en la cola 'cfe-processing'.");
        }

        return Command::SUCCESS;
    }
}
