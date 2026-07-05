<?php

namespace App\Console\Commands;

use App\Models\TesCfePendiente;
use Illuminate\Console\Command;

class CfeDetectDuplicates extends Command
{
    protected $signature = 'cfe:detect-duplicates
        {--fix : Marcar duplicados sospechosos automáticamente}
        {--days=7 : Ventana de días hacia atrás para buscar duplicados}';

    protected $description = 'Detecta CFEs duplicados sospechosos por (fecha, monto, receptor_documento)';

    public function handle(): int
    {
        $days = (int)$this->option('days');
        $fix = $this->option('fix');
        $since = now()->subDays($days);

        $this->info("Buscando duplicados sospechosos en los últimos {$days} días...");

        $pendientes = TesCfePendiente::where('created_at', '>=', $since)
            ->whereNotNull('datos_extraidos')
            ->get();

        $grupos = [];
        foreach ($pendientes as $p) {
            $datos = $p->datos_extraidos;
            $clave = implode('|', [
                $datos['serie'] ?? '',
                $datos['numero'] ?? '',
                $datos['monto'] ?? '',
                $datos['receptor_documento'] ?? '',
            ]);
            $grupos[$clave][] = $p;
        }

        $duplicados = array_filter($grupos, fn($g) => count($g) > 1);

        if (empty($duplicados)) {
            $this->info("✓ No se encontraron duplicados sospechosos.");
            return Command::SUCCESS;
        }

        $this->warn("Se encontraron " . count($duplicados) . " grupos de posibles duplicados:");
        $this->newLine();

        $marcados = 0;
        foreach ($duplicados as $clave => $items) {
            [$serie, $numero, $monto, $doc] = explode('|', $clave);
            $this->line("  Serie: {$serie} | Número: {$numero} | Monto: {$monto} | Doc: {$doc}");

            $tabla = [];
            foreach ($items as $item) {
                $tabla[] = [
                    $item->id,
                    $item->tipo_cfe,
                    $item->estado,
                    $item->created_at->format('d/m/Y H:i'),
                    $item->pdf_hash ? substr($item->pdf_hash, 0, 12) . '...' : '-',
                ];
            }
            $this->table(['ID', 'Tipo', 'Estado', 'Creado', 'Hash'], $tabla);

            if ($fix) {
                $keep = $items[0];
                foreach (array_slice($items, 1) as $dup) {
                    if ($dup->estado === 'pendiente') {
                        $dup->datos_modificados = array_merge($dup->datos_modificados ?? [], [
                            '_duplicado_de' => $keep->id,
                            '_marcado_auto' => now()->toDateTimeString(),
                        ]);
                        $dup->estado = 'rechazado';
                        $dup->motivo_rechazo = "Duplicado sospechoso de #{$keep->id} ({$keep->tipo_cfe} {$keep->serie}-{$keep->numero})";
                        $dup->save();
                        $marcados++;
                    }
                }
            }

            $this->newLine();
        }

        if ($fix) {
            $this->info("✓ {$marcados} duplicados marcados como rechazados.");
        } else {
            $this->warn("Usa --fix para marcar automáticamente los duplicados como rechazados.");
        }

        return Command::SUCCESS;
    }
}
