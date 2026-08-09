<?php

namespace App\Console\Commands;

use App\Models\Tesoreria\LibroDiario;
use Illuminate\Console\Command;

class RecalcularSaldosLibroDiarioCommand extends Command
{
    protected $signature = 'libro-diario:recalcular-saldos
                            {--dry-run : Muestra los cambios sin aplicarlos}';

    protected $description = 'Recalcula los saldos acumulados de todas las subcuentas del libro diario, '
                            . 'excluyendo los registros con soft delete. El saldo corre por identidad '
                            . 'y nunca es negativo. Útil para corregir saldos desactualizados luego de '
                            . 'borrados lógicos o cambios de identidad.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo --dry-run: se muestran los cambios pero NO se aplican.');
        }

        // Obtener todas las combinaciones únicas (medio_id, concepto_id, detalle_id)
        // presentes en registros activos (sin soft delete).
        $subcuentas = LibroDiario::select('medio_id', 'concepto_id', 'detalle_id')
            ->groupBy('medio_id', 'concepto_id', 'detalle_id')
            ->get();

        if ($subcuentas->isEmpty()) {
            $this->info('No hay subcuentas en el libro diario.');
            return self::SUCCESS;
        }

        $this->info("Se encontraron {$subcuentas->count()} subcuenta(s). Procesando...");
        $bar = $this->output->createProgressBar($subcuentas->count());
        $bar->start();

        $totalRegistros = 0;
        $totalCambios   = 0;

        foreach ($subcuentas as $subcuenta) {
            $medioId    = $subcuenta->medio_id;
            $conceptoId = $subcuenta->concepto_id;
            $detalleId  = $subcuenta->detalle_id;

            // Traer los registros activos de esta subcuenta en orden cronológico.
            $registros = LibroDiario::where('medio_id', $medioId)
                ->where('concepto_id', $conceptoId)
                ->where('detalle_id', $detalleId)
                ->orderBy('fecha')
                ->orderBy('id')
                ->get();

            // Saldo corrido por identidad (nunca negativo).
            $saldosPorIdentidad = [];
            foreach ($registros as $registro) {
                $identidad = mb_strtoupper(trim((string) ($registro->identidad ?? '')));
                $saldosPorIdentidad[$identidad] = max(
                    0,
                    ($saldosPorIdentidad[$identidad] ?? 0) + $registro->monto * $registro->signo_efectivo
                );
                $saldoRedondeado = round($saldosPorIdentidad[$identidad], 2);

                if ((float) $registro->saldo !== $saldoRedondeado) {
                    $totalCambios++;
                    if (!$dryRun) {
                        $registro->update(['saldo' => $saldoRedondeado]);
                    } else {
                        $this->newLine();
                        $this->line(sprintf(
                            '  [ID %d] medio=%d concepto=%d detalle=%d identidad=%s | saldo actual: %s → nuevo: %s',
                            $registro->id,
                            $medioId,
                            $conceptoId,
                            $detalleId,
                            $identidad !== '' ? $identidad : '(sin identidad)',
                            number_format((float) $registro->saldo, 2, ',', '.'),
                            number_format($saldoRedondeado, 2, ',', '.')
                        ));
                    }
                }

                $totalRegistros++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Registros procesados : {$totalRegistros}");

        if ($dryRun) {
            $this->warn("Registros que cambiarían: {$totalCambios} (no se aplicó ningún cambio)");
        } else {
            $this->info("Registros actualizados: {$totalCambios}");
            $this->info('Saldos recalculados correctamente.');
        }

        return self::SUCCESS;
    }
}
