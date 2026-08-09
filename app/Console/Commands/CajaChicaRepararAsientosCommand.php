<?php

namespace App\Console\Commands;

use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Pendiente;
use App\Services\Tesoreria\CajaChicaAsientosService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CajaChicaRepararAsientosCommand extends Command
{
    protected $signature = 'caja-chica:reparar-asientos
                            {--desde= : Fecha desde (Y-m-d) para filtrar registros por created_at, por defecto sin límite}
                            {--dry-run : Muestra qué se repararía sin aplicarlo}';

    protected $description = 'Registra los asientos de Libro Diario faltantes para Pendientes, '
                            . 'Movimientos y Pagos de Caja Chica que quedaron sin asientos. Útil para '
                            . 'corregir registros creados antes de que la centralización en CajaChicaService '
                            . 'hiciera el registro automático.';

    private int $registrados       = 0;
    private int $omitidos          = 0;
    private bool $dryRun          = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $desde = $this->option('desde');
        $desdeStr = $desde ? Carbon::parse($desde)->startOfDay()->toDateTimeString() : null;

        if ($this->dryRun) {
            $this->warn('Modo --dry-run: se muestra lo que se registraría pero NO se aplica.');
        }

        if ($desdeStr) {
            $this->info("Filtrando registros posteriores a: {$desde} 00:00:00");
        }

        $service = app(CajaChicaAsientosService::class);

        $this->repararPendientes($service, $desdeStr);
        $this->repararPagos($service, $desdeStr);
        $this->repararMovimientos($service, $desdeStr);

        $this->newLine(2);
        $this->info("Registrados     : {$this->registrados}");
        $this->info("Ya cubiertos     : {$this->omitidos}");

        return self::SUCCESS;
    }

    private function repararPendientes(CajaChicaAsientosService $service, ?string $desdeStr): void
    {
        $query = Pendiente::with('dependencia');
        if ($desdeStr) {
            $query->where('created_at', '>=', $desdeStr);
        }

        $pendientes = $query->get();
        $this->newLine();
        $this->info("Pendientes a revisar: {$pendientes->count()}");

        foreach ($pendientes as $pendiente) {
            $tieneAsientos = LibroDiario::where('cch_origen_type', 'pendiente')
                ->where('cch_origen_id', $pendiente->idPendientes)
                ->exists();

            if ($tieneAsientos) {
                $this->omitidos++;
                continue;
            }

            $this->line(sprintf(
                '[pendiente #%d] falta redistribución fondo → pendiente (monto $%s)',
                $pendiente->idPendientes,
                number_format((float) $pendiente->montoPendientes, 2, ',', '.')
            ));

            if (!$this->dryRun) {
                try {
                    $service->registrarRedistribucionPendiente($pendiente);
                    $this->registrados++;
                    $this->info("  ✔ pendiente -> asientos creados");
                } catch (\Exception $e) {
                    $this->error("  ✘ pendiente #{$pendiente->idPendientes}: {$e->getMessage()}");
                }
            } else {
                $this->registrados++;
            }
        }
    }

    private function repararPagos(CajaChicaAsientosService $service, ?string $desdeStr): void
    {
        $query = Pago::with('acreedor');
        if ($desdeStr) {
            $query->where('created_at', '>=', $desdeStr);
        }

        $pagos = $query->get();
        $this->newLine();
        $this->info("Pagos a revisar: {$pagos->count()}");

        foreach ($pagos as $pago) {
            $tieneAsientos = $this->hasAsientos('pago', $pago->idPagos);

            if (!$tieneAsientos) {
                if ((float) $pago->montoPagos > 0) {
                    $this->line(sprintf(
                        '  [pago #%d] falta redistribución fondo → pago ($%s)',
                        $pago->idPagos,
                        number_format((float) $pago->montoPagos, 2, ',', '.')
                    ));
                    if ($this->dryRun) {
                        $this->registrados++;
                    } else {
                        try {
                            $service->registrarRedistribucionPago($pago);
                            $this->registrados++;
                            $this->info('  ✔ pago #' . $pago->idPagos . ' redistribución creada');
                        } catch (\Exception $e) {
                            $this->error('  ✗ pago #' . $pago->idPagos . ': ' . $e->getMessage());
                        }
                    }
                }
            }

            $rendido = (float) ($pago->rendidoPagos ?? 0);
            $reintegrado = (float) ($pago->reintegradoPagos ?? 0);
            $recuperado = (float) ($pago->recuperadoPagos ?? 0);
            $tienerendicion = $rendido > 0 || $reintegrado > 0;
            $tienerRecuperacion = $recuperado > 0;

            if (($tienerendicion || $tienerRecuperacion) && !$tieneAsientos) {
                if ($tienerendicion) {
                    $this->line('    - falta asiento de rendición');
                }
                if ($tienerRecuperacion) {
                    $this->line('    - falta asiento de recuperación');
                }
                if ($this->dryRun) {
                    $this->registrados++;
                } else {
                    try {
                        if ($tienerendicion) {
                            $service->registrarAsientosRendicionPago($pago);
                            $this->registrados++;
                            $this->info('  ✔ pago #' . $pago->idPagos . ' rendición registrada');
                        }
                        if ($tienerRecuperacion) {
                            $service->registrarAsientosRecuperacionPago($pago, $recuperado);
                            $this->registrados++;
                            $this->info('  ✔ pago #' . $pago->idPagos . ' recuperación registrada');
                        }
                    } catch (\Exception $e) {
                        $this->error('  ✗ pago #' . $pago->idPagos . ': ' . $e->getMessage());
                    }
                }
            } elseif ($tieneAsientos) {
                $this->salted();
            }
        }
    }

    private function repararMovimientos(CajaChicaAsientosService $service, ?string $desdeStr): void
    {
        $query = Movimiento::with('pendiente.dependencia');
        if ($desdeStr) {
            $query->where('created_at', '>=', $desdeStr);
        }

        $movimientos = $query->get();
        $this->newLine();
        $this->info("Movimientos a revisar: {$movimientos->count()}");

        foreach ($movimientos as $movimiento) {
            $rendido = (float) ($movimiento->rendido ?? 0);
            $reintegrado = (float) ($movimiento->reintegrado ?? 0);
            $recuperado = (float) ($movimiento->recuperado ?? 0);

            if ($rendido <= 0 && $reintegrado <= 0 && $recuperado <= 0) {
                $this->salted();
                continue;
            }

            $tienesAsientos = $this->hasAsientos('movimiento', $movimiento->idMovimientos);

            if ($tienesAsientos) {
                $this->salted();
                continue;
            }

            $this->line(sprintf(
                '  [movimiento #%d] rendido=$%s reintegrado=$%s recuperado=$%s',
                $movimiento->idMovimientos,
                number_format($rendido, 2, ',', '.'),
                number_format($reintegrado, 2, ',', '.'),
                number_format($recuperado, 2, ',', '.')
            ));

            if ($this->dryRun) {
                $this->registrados++;
                continue;
            }

            try {
                if ($rendido > 0 || $reintegrado > 0) {
                    $service->registrarAsientosRendicionPendiente($movimiento);
                    $this->registrados++;
                    $this->info('  ✔ movimiento #' . $movimiento->idMovimientos . ' rendición registrada');
                }
                if ($recuperado > 0) {
                    $service->registrarAsientosRecuperacion($movimiento);
                    $this->registrados++;
                    $this->info('  ✔ movimiento #' . $movimiento->idMovimientos . ' recuperación registrada');
                }
            } catch (\Exception $e) {
                $this->error('  ✗ movimiento #' . $movimiento->idMovimientos . ': ' . $e->getMessage());
            }
        }
    }

    private function hasAsientos(string $tipo, int $id): bool
    {
        return LibroDiario::where('cch_origen_type', $tipo)
            ->where('cch_origen_id', $id)
            ->exists();
    }

    private function salted(): void
    {
        $this->omitidos++;
    }
}