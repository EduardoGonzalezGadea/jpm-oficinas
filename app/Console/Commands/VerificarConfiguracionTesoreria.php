<?php

namespace App\Console\Commands;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\LbTipo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarConfiguracionTesoreria extends Command
{
    protected $signature = 'tesoreria:verificar 
                            {--fix : Crea automáticamente los elementos faltantes}
                            {--detallado : Muestra información adicional de cada verificación}';

    protected $description = 'Verifica que existan todos los conceptos, detalles y medios de pago requeridos para el módulo de Tesorería';

    private int $erroresEncontrados = 0;
    private int $elementosCreados = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Verificación de Configuración del Módulo de Tesorería');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $fix = $this->option('fix');
        $detallado = $this->option('detallado');

        if ($fix) {
            $this->warn('⚠  Modo reparación activado: se crearán elementos faltantes');
            $this->newLine();
        }

        // Verificar tipos de asiento
        $this->verificarTipos($fix, $detallado);

        // Verificar conceptos
        $this->verificarConceptos($fix, $detallado);

        // Verificar detalles de Caja Chica
        $this->verificarDetallesCajaChica($fix, $detallado);

        // Verificar detalles de Recaudaciones
        $this->verificarDetallesRecaudaciones($fix, $detallado);

        // Verificar medios de pago
        $this->verificarMediosDePago($fix, $detallado);

        // Resumen final
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        
        if ($this->erroresEncontrados === 0) {
            $this->info('✓ Verificación completada: Configuración correcta');
            
            if ($this->elementosCreados > 0) {
                $this->info("  Se crearon {$this->elementosCreados} elemento(s)");
            }
            
            return 0;
        }

        if ($fix) {
            $this->error("✗ Se encontraron {$this->erroresEncontrados} problema(s)");
            $this->info("  Se crearon {$this->elementosCreados} elemento(s)");
            
            if ($this->erroresEncontrados > $this->elementosCreados) {
                $this->newLine();
                $this->warn('  Algunos problemas no pudieron repararse automáticamente.');
                $this->warn('  Revise los errores anteriores y créelos manualmente.');
            }
        } else {
            $this->error("✗ Se encontraron {$this->erroresEncontrados} problema(s)");
            $this->newLine();
            $this->info('  Ejecute con --fix para intentar repararlos automáticamente:');
            $this->line('  php artisan tesoreria:verificar --fix');
        }

        $this->info('═══════════════════════════════════════════════════════════════');

        return $this->erroresEncontrados > 0 ? 1 : 0;
    }

    private function verificarTipos(bool $fix, bool $detallado): void
    {
        $this->line('► Verificando tipos de asiento...');

        $tiposRequeridos = [
            'Entrada' => 1,
            'Salida' => -1,
        ];

        foreach ($tiposRequeridos as $nombre => $signo) {
            $tipo = LbTipo::where('nombre', $nombre)->first();

            if (!$tipo) {
                $this->erroresEncontrados++;
                $this->error("  ✗ Falta tipo: {$nombre}");

                if ($fix) {
                    try {
                        LbTipo::create([
                            'nombre' => $nombre,
                            'signo' => $signo,
                        ]);
                        $this->elementosCreados++;
                        $this->info("    → Creado tipo: {$nombre}");
                    } catch (\Exception $e) {
                        $this->error("    → Error al crear: {$e->getMessage()}");
                    }
                }
            } elseif ($detallado) {
                $this->line("  ✓ {$nombre} (ID: {$tipo->id})");
            }
        }
    }

    private function verificarConceptos(bool $fix, bool $detallado): void
    {
        $this->line('► Verificando conceptos...');

        $conceptosRequeridos = [
            LbConcepto::CAJA_CHICA,
            LbConcepto::RECAUDACION_222,
            LbConcepto::RECAUDACION_DIARIA,
        ];

        foreach ($conceptosRequeridos as $nombreConcepto) {
            $concepto = LbConcepto::where('nombre', $nombreConcepto)->first();

            if (!$concepto) {
                $this->erroresEncontrados++;
                $this->error("  ✗ Falta concepto: {$nombreConcepto}");

                if ($fix) {
                    try {
                        $concepto = LbConcepto::create(['nombre' => $nombreConcepto]);
                        $this->elementosCreados++;
                        $this->info("    → Creado concepto: {$nombreConcepto} (ID: {$concepto->id})");
                    } catch (\Exception $e) {
                        $this->error("    → Error al crear: {$e->getMessage()}");
                    }
                }
            } elseif ($detallado) {
                $this->line("  ✓ {$nombreConcepto} (ID: {$concepto->id})");
            }
        }
    }

    private function verificarDetallesCajaChica(bool $fix, bool $detallado): void
    {
        $this->line('► Verificando detalles de Caja Chica...');

        $concepto = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->first();

        if (!$concepto) {
            $this->error('  ✗ No se puede verificar detalles: concepto Caja Chica no existe');
            return;
        }

        $detallesRequeridos = [
            LbDetalle::FONDO_FIJO,
            LbDetalle::PENDIENTE,
            LbDetalle::PAGOS,
        ];

        foreach ($detallesRequeridos as $nombreDetalle) {
            $detalle = LbDetalle::where('concepto_id', $concepto->id)
                ->where('nombre', $nombreDetalle)
                ->first();

            if (!$detalle) {
                $this->erroresEncontrados++;
                $this->error("  ✗ Falta detalle: {$nombreDetalle}");

                if ($fix) {
                    try {
                        $detalle = LbDetalle::create([
                            'concepto_id' => $concepto->id,
                            'nombre' => $nombreDetalle,
                        ]);
                        $this->elementosCreados++;
                        $this->info("    → Creado detalle: {$nombreDetalle} (ID: {$detalle->id})");
                    } catch (\Exception $e) {
                        $this->error("    → Error al crear: {$e->getMessage()}");
                    }
                }
            } elseif ($detallado) {
                $this->line("  ✓ {$nombreDetalle} (ID: {$detalle->id})");
            }
        }
    }

    private function verificarDetallesRecaudaciones(bool $fix, bool $detallado): void
    {
        $this->line('► Verificando detalles de Recaudaciones...');

        // Recaudación Artículo 222
        $concepto222 = LbConcepto::where('nombre', LbConcepto::RECAUDACION_222)->first();

        if ($concepto222) {
            $detalle222 = LbDetalle::where('concepto_id', $concepto222->id)
                ->where('nombre', LbDetalle::RECAUDACIONES_VARIAS_222)
                ->first();

            if (!$detalle222) {
                $this->erroresEncontrados++;
                $this->error("  ✗ Falta detalle: " . LbDetalle::RECAUDACIONES_VARIAS_222);

                if ($fix) {
                    try {
                        $detalle222 = LbDetalle::create([
                            'concepto_id' => $concepto222->id,
                            'nombre' => LbDetalle::RECAUDACIONES_VARIAS_222,
                        ]);
                        $this->elementosCreados++;
                        $this->info("    → Creado detalle: " . LbDetalle::RECAUDACIONES_VARIAS_222 . " (ID: {$detalle222->id})");
                    } catch (\Exception $e) {
                        $this->error("    → Error al crear: {$e->getMessage()}");
                    }
                }
            } elseif ($detallado) {
                $this->line("  ✓ " . LbDetalle::RECAUDACIONES_VARIAS_222 . " (ID: {$detalle222->id})");
            }
        } else {
            $this->error('  ✗ No se puede verificar detalles: concepto Recaudación Artículo 222 no existe');
        }

        // Recaudación Diaria
        $conceptoDiaria = LbConcepto::where('nombre', LbConcepto::RECAUDACION_DIARIA)->first();

        if ($conceptoDiaria) {
            $detalleDiaria = LbDetalle::where('concepto_id', $conceptoDiaria->id)
                ->where('nombre', LbDetalle::OTRAS_RECAUDACIONES_VARIAS)
                ->first();

            if (!$detalleDiaria) {
                $this->erroresEncontrados++;
                $this->error("  ✗ Falta detalle: " . LbDetalle::OTRAS_RECAUDACIONES_VARIAS);

                if ($fix) {
                    try {
                        $detalleDiaria = LbDetalle::create([
                            'concepto_id' => $conceptoDiaria->id,
                            'nombre' => LbDetalle::OTRAS_RECAUDACIONES_VARIAS,
                        ]);
                        $this->elementosCreados++;
                        $this->info("    → Creado detalle: " . LbDetalle::OTRAS_RECAUDACIONES_VARIAS . " (ID: {$detalleDiaria->id})");
                    } catch (\Exception $e) {
                        $this->error("    → Error al crear: {$e->getMessage()}");
                    }
                }
            } elseif ($detallado) {
                $this->line("  ✓ " . LbDetalle::OTRAS_RECAUDACIONES_VARIAS . " (ID: {$detalleDiaria->id})");
            }
        } else {
            $this->error('  ✗ No se puede verificar detalles: concepto Recaudación Diaria no existe');
        }
    }

    private function verificarMediosDePago(bool $fix, bool $detallado): void
    {
        $this->line('► Verificando medios de pago...');

        // Efectivo
        $efectivo = MedioDePago::where('nombre', MedioDePago::EFECTIVO)
            ->where('activo', true)
            ->first();

        if (!$efectivo) {
            $this->erroresEncontrados++;
            $this->error("  ✗ Falta medio de pago: " . MedioDePago::EFECTIVO);

            if ($fix) {
                try {
                    $efectivo = MedioDePago::create([
                        'nombre' => MedioDePago::EFECTIVO,
                        'nombre_corto' => 'Efectivo',
                        'activo' => true,
                        'es_libro_diario' => true,
                        'es_recaudacion' => false,
                        'contado' => true,
                        'orden' => 1,
                    ]);
                    $this->elementosCreados++;
                    $this->info("    → Creado medio de pago: " . MedioDePago::EFECTIVO . " (ID: {$efectivo->id})");
                } catch (\Exception $e) {
                    $this->error("    → Error al crear: {$e->getMessage()}");
                }
            }
        } elseif ($detallado) {
            $this->line("  ✓ " . MedioDePago::EFECTIVO . " (ID: {$efectivo->id})");
        }

        // Transferencia (búsqueda flexible)
        $transferencia = MedioDePago::where('nombre', 'like', '%' . MedioDePago::TRANSFERENCIA . '%')
            ->where('activo', true)
            ->first();

        if (!$transferencia) {
            $this->erroresEncontrados++;
            $this->error("  ✗ Falta medio de pago que contenga: " . MedioDePago::TRANSFERENCIA);

            if ($fix) {
                try {
                    $transferencia = MedioDePago::create([
                        'nombre' => 'Transferencia Bancaria',
                        'nombre_corto' => 'Transferencia',
                        'activo' => true,
                        'es_libro_diario' => true,
                        'es_recaudacion' => true,
                        'contado' => false,
                        'orden' => 2,
                    ]);
                    $this->elementosCreados++;
                    $this->info("    → Creado medio de pago: Transferencia Bancaria (ID: {$transferencia->id})");
                } catch (\Exception $e) {
                    $this->error("    → Error al crear: {$e->getMessage()}");
                }
            }
        } elseif ($detallado) {
            $this->line("  ✓ {$transferencia->nombre} (ID: {$transferencia->id})");
        }
    }
}
