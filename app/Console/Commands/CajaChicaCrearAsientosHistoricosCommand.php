<?php

namespace App\Console\Commands;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pago;
use App\Models\Tesoreria\Pendiente;
use App\Services\Tesoreria\CajaChicaAsientosService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CajaChicaCrearAsientosHistoricosCommand extends Command
{
    protected $signature = 'caja-chica:crear-asientos-historicos
                            {--caja-chica-id= : ID específico de la caja chica a procesar}
                            {--mes= : Mes de la caja chica (ej: enero, febrero)}
                            {--anio= : Año de la caja chica (ej: 2026)}
                            {--dry-run : Muestra qué se crearía sin aplicarlo}
                            {--skip-confirmacion : Omite la confirmación interactiva}';

    protected $description = 'Crea los asientos del libro diario para los registros históricos de caja chica '
                            . 'que fueron creados antes de que se implementara el sistema. '
                            . 'Respeta las fechas y montos originales de cada operación. '
                            . 'Útil para completar el libro diario con movimientos históricos faltantes.';

    private int $fondoFijoCreado       = 0;
    private int $pendientesCreados     = 0;
    private int $pagosCreados          = 0;
    private int $movimientosCreados    = 0;
    private int $omitidos              = 0;
    private bool $dryRun              = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $skipConfirmacion = (bool) $this->option('skip-confirmacion');

        if ($this->dryRun) {
            $this->warn('Modo --dry-run: se muestra lo que se crearía pero NO se aplica.');
            $this->newLine();
        }

        $service = app(CajaChicaAsientosService::class);

        // Determinar qué cajas chicas procesar
        $cajasChicas = $this->obtenerCajasChicas();

        if ($cajasChicas->isEmpty()) {
            $this->error('No se encontraron cajas chicas para procesar.');
            return self::FAILURE;
        }

        $this->info("Se procesarán {$cajasChicas->count()} caja(s) chica(s):");
        foreach ($cajasChicas as $caja) {
            $this->line("  - ID: {$caja->idCajaChica} | Mes: {$caja->mes} {$caja->anio} | Fondo: \$ " . number_format($caja->montoCajaChica, 2, ',', '.'));
        }
        $this->newLine();

        if (!$skipConfirmacion && !$this->dryRun) {
            if (!$this->confirm('¿Desea continuar con la creación de asientos históricos?')) {
                $this->info('Operación cancelada por el usuario.');
                return self::SUCCESS;
            }
            $this->newLine();
        }

        foreach ($cajasChicas as $cajaChica) {
            $this->procesarCajaChica($cajaChica, $service);
        }

        $this->mostrarResumen();

        return self::SUCCESS;
    }

    private function obtenerCajasChicas()
    {
        if ($this->option('caja-chica-id')) {
            $id = (int) $this->option('caja-chica-id');
            $caja = CajaChica::find($id);
            return $caja ? collect([$caja]) : collect();
        }

        if ($this->option('mes') && $this->option('anio')) {
            $mes = $this->option('mes');
            $anio = (int) $this->option('anio');

            return CajaChica::where(function ($query) use ($mes) {
                $query->where('mes', $mes)
                      ->orWhere('mes', ucfirst($mes))
                      ->orWhere('mes', strtolower($mes));
            })
            ->where('anio', $anio)
            ->get();
        }

        // Por defecto, procesar todas las cajas chicas
        return CajaChica::orderBy('anio')->orderBy('mes')->get();
    }

    private function procesarCajaChica(CajaChica $cajaChica, CajaChicaAsientosService $service): void
    {
        $this->info("=== Procesando Caja Chica: {$cajaChica->mes} {$cajaChica->anio} (ID: {$cajaChica->idCajaChica}) ===");
        $this->newLine();

        // 1. Crear asiento de fondo fijo (constitución inicial)
        $this->crearAsientoFondoFijo($cajaChica, $service);

        // 2. Procesar pendientes (redistribuciones fondo → pendiente)
        $this->procesarPendientes($cajaChica, $service);

        // 3. Procesar pagos directos (redistribuciones fondo → pago + rendiciones + recuperaciones)
        $this->procesarPagos($cajaChica, $service);

        // 4. Procesar movimientos de pendientes (rendiciones y recuperaciones)
        $this->procesarMovimientos($cajaChica, $service);

        $this->newLine();
    }

    private function crearAsientoFondoFijo(CajaChica $cajaChica, CajaChicaAsientosService $service): void
    {
        // Verificar si ya existe un asiento de fondo fijo para esta caja chica
        $tieneAsientoFondoFijo = LibroDiario::where('cch_origen_type', 'caja_chica')
            ->where('cch_origen_id', $cajaChica->idCajaChica)
            ->exists();

        if ($tieneAsientoFondoFijo) {
            $this->line("  [Fondo Fijo] Ya existe asiento para fondo fijo de \$ " . number_format($cajaChica->montoCajaChica, 2, ',', '.'));
            $this->omitidos++;
            return;
        }

        $fechaCreacion = $cajaChica->created_at ? $cajaChica->created_at->format('Y-m-d') : now()->format('Y-m-d');
        $monto = (float) $cajaChica->montoCajaChica;

        if ($monto <= 0) {
            $this->line("  [Fondo Fijo] Monto $0, no se crea asiento.");
            $this->omitidos++;
            return;
        }

        $this->line(sprintf(
            '  [Fondo Fijo] Creando asiento de constitución: \$ %s (fecha: %s)',
            number_format($monto, 2, ',', '.'),
            Carbon::parse($fechaCreacion)->format('d/m/Y')
        ));

        if (!$this->dryRun) {
            try {
                $service->registrarAjusteFondoFijoPorSaldoLibroDiario(
                    $cajaChica,
                    $monto,
                    $fechaCreacion
                );
                $this->fondoFijoCreado++;
                $this->info('    ✔ Asiento de fondo fijo creado');
            } catch (\Exception $e) {
                $this->error("    ✘ Error al crear fondo fijo: {$e->getMessage()}");
            }
        } else {
            $this->fondoFijoCreado++;
        }
    }

    private function procesarPendientes(CajaChica $cajaChica, CajaChicaAsientosService $service): void
    {
        $pendientes = Pendiente::where('relCajaChica', $cajaChica->idCajaChica)
            ->with('dependencia')
            ->orderBy('fechaPendientes')
            ->orderBy('pendiente')
            ->get();

        if ($pendientes->isEmpty()) {
            $this->line('  [Pendientes] No hay pendientes para procesar.');
            return;
        }

        $this->info("  [Pendientes] Procesando {$pendientes->count()} pendiente(s)...");

        foreach ($pendientes as $pendiente) {
            $tieneAsiento = LibroDiario::where('cch_origen_type', 'pendiente')
                ->where('cch_origen_id', $pendiente->idPendientes)
                ->exists();

            if ($tieneAsiento) {
                $this->omitidos++;
                continue;
            }

            $dependenciaNombre = $pendiente->dependencia ? $pendiente->dependencia->dependencia : '(Sin dependencia)';
            $monto = (float) $pendiente->montoPendientes;
            $fecha = $pendiente->fechaPendientes->format('Y-m-d');

            $this->line(sprintf(
                '    Pendiente #%d: %s - \$ %s (fecha: %s)',
                $pendiente->pendiente,
                $dependenciaNombre,
                number_format($monto, 2, ',', '.'),
                Carbon::parse($fecha)->format('d/m/Y')
            ));

            if (!$this->dryRun) {
                try {
                    $service->registrarRedistribucionPendiente($pendiente);
                    $this->pendientesCreados++;
                    $this->info('      ✔ Redistribución fondo → pendiente creada');
                } catch (\Exception $e) {
                    $this->error("      ✘ Error: {$e->getMessage()}");
                }
            } else {
                $this->pendientesCreados++;
            }
        }
    }

    private function procesarPagos(CajaChica $cajaChica, CajaChicaAsientosService $service): void
    {
        $pagos = Pago::where('relCajaChica_Pagos', $cajaChica->idCajaChica)
            ->with('acreedor')
            ->orderBy('fechaEgresoPagos')
            ->get();

        if ($pagos->isEmpty()) {
            $this->line('  [Pagos Directos] No hay pagos para procesar.');
            return;
        }

        $this->info("  [Pagos Directos] Procesando {$pagos->count()} pago(s)...");

        foreach ($pagos as $pago) {
            $tieneAsiento = LibroDiario::where('cch_origen_type', 'pago')
                ->where('cch_origen_id', $pago->idPagos)
                ->exists();

            if ($tieneAsiento) {
                $this->omitidos++;
                continue;
            }

            $acreedorNombre = $pago->acreedor ? $pago->acreedor->acreedor : '(Sin acreedor)';
            $monto = (float) $pago->montoPagos;
            $fecha = $pago->fechaEgresoPagos->format('Y-m-d');
            $rendido = (float) ($pago->rendidoPagos ?? 0);
            $recuperado = (float) ($pago->recuperadoPagos ?? 0);

            $this->line(sprintf(
                '    Pago #%d: %s - \$ %s (fecha: %s)',
                $pago->idPagos,
                $acreedorNombre,
                number_format($monto, 2, ',', '.'),
                Carbon::parse($fecha)->format('d/m/Y')
            ));

            if (!$this->dryRun) {
                try {
                    // 1. Redistribución fondo → pago
                    $service->registrarRedistribucionPago($pago);
                    $this->pagosCreados++;
                    $this->info('      ✔ Redistribución fondo → pago creada');

                    // 2. Si tiene rendición, registrar asiento de rendición
                    if ($rendido > 0) {
                        $service->registrarAsientosRendicionPago($pago);
                        $this->info("      ✔ Rendición registrada: \$ " . number_format($rendido, 2, ',', '.'));
                    }

                    // 3. Si tiene recuperación, registrar asiento de recuperación
                    if ($recuperado > 0) {
                        $service->registrarAsientosRecuperacionPago($pago, $recuperado);
                        $this->info("      ✔ Recuperación registrada: \$ " . number_format($recuperado, 2, ',', '.'));
                    }
                } catch (\Exception $e) {
                    $this->error("      ✘ Error: {$e->getMessage()}");
                }
            } else {
                $this->pagosCreados++;
            }
        }
    }

    private function procesarMovimientos(CajaChica $cajaChica, CajaChicaAsientosService $service): void
    {
        // Obtener movimientos de pendientes asociados a esta caja chica
        $movimientos = Movimiento::whereHas('pendiente', function ($query) use ($cajaChica) {
            $query->where('relCajaChica', $cajaChica->idCajaChica);
        })
        ->with('pendiente.dependencia')
        ->orderBy('fechaMovimientos')
        ->get();

        if ($movimientos->isEmpty()) {
            $this->line('  [Movimientos] No hay movimientos para procesar.');
            return;
        }

        $this->info("  [Movimientos] Procesando {$movimientos->count()} movimiento(s)...");

        foreach ($movimientos as $movimiento) {
            $tieneAsiento = LibroDiario::where('cch_origen_type', 'movimiento')
                ->where('cch_origen_id', $movimiento->idMovimientos)
                ->exists();

            if ($tieneAsiento) {
                $this->omitidos++;
                continue;
            }

            $rendido = (float) ($movimiento->rendido ?? 0);
            $reintegrado = (float) ($movimiento->reintegrado ?? 0);
            $recuperado = (float) ($movimiento->recuperado ?? 0);

            // Si el movimiento no tiene operaciones, omitir
            if ($rendido <= 0 && $reintegrado <= 0 && $recuperado <= 0) {
                $this->omitidos++;
                continue;
            }

            $pendiente = $movimiento->pendiente;
            $numeroPendiente = $pendiente ? $pendiente->pendiente : $movimiento->relPendiente;
            $fecha = $movimiento->fechaMovimientos->format('Y-m-d');

            $this->line(sprintf(
                '    Movimiento #%d (Pendiente #%d) - fecha: %s',
                $movimiento->idMovimientos,
                $numeroPendiente,
                Carbon::parse($fecha)->format('d/m/Y')
            ));

            if ($rendido > 0 || $reintegrado > 0) {
                $this->line("      Rendido: \$ " . number_format($rendido, 2, ',', '.'));
            }
            if ($recuperado > 0) {
                $this->line("      Recuperado: \$ " . number_format($recuperado, 2, ',', '.'));
            }

            if (!$this->dryRun) {
                try {
                    // 1. Si tiene rendición, registrar asiento de rendición
                    if ($rendido > 0 || $reintegrado > 0) {
                        $service->registrarAsientosRendicionPendiente($movimiento);
                        $this->movimientosCreados++;
                        $this->info('      ✔ Rendición de pendiente registrada');
                    }

                    // 2. Si tiene recuperación, registrar asiento de recuperación
                    if ($recuperado > 0) {
                        $service->registrarAsientosRecuperacion($movimiento);
                        $this->movimientosCreados++;
                        $this->info('      ✔ Recuperación de pendiente registrada');
                    }
                } catch (\Exception $e) {
                    $this->error("      ✘ Error: {$e->getMessage()}");
                }
            } else {
                $this->movimientosCreados++;
            }
        }
    }

    private function mostrarResumen(): void
    {
        $this->newLine(2);
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                       RESUMEN                             ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line("Fondos fijos creados      : {$this->fondoFijoCreado}");
        $this->line("Pendientes procesados     : {$this->pendientesCreados}");
        $this->line("Pagos procesados          : {$this->pagosCreados}");
        $this->line("Movimientos procesados    : {$this->movimientosCreados}");
        $this->line("Registros omitidos        : {$this->omitidos}");
        $this->info('═══════════════════════════════════════════════════════════');

        $total = $this->fondoFijoCreado + $this->pendientesCreados + $this->pagosCreados + $this->movimientosCreados;

        if ($this->dryRun) {
            $this->newLine();
            $this->warn("Modo --dry-run: se registrarían {$total} asiento(s) en total.");
            $this->info('Ejecute el comando sin --dry-run para aplicar los cambios.');
        } else {
            $this->newLine();
            $this->info("Se crearon {$total} asiento(s) en el libro diario.");
            $this->info('Recomendación: ejecute "php artisan libro-diario:recalcular-saldos" para actualizar los saldos.');
        }
    }
}
