<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarMediosPagoCommand extends Command
{
    protected $signature = 'mediospago:verificar {--fix : Corregir FK sin mapeo usando resolverPorTexto}';

    protected $description = 'Verifica la integridad de la migración de medios de pago';

    public function handle(): int
    {
        $this->info('=== Verificación de Medios de Pago ===');
        $errores = 0;

        $this->line("\n--- Catálogo de medios ---");
        $count = DB::table('tes_medio_de_pagos')->count();
        $this->line("Total registros en tes_medio_de_pagos: {$count}");

        $medios = DB::table('tes_medio_de_pagos')
            ->orderBy('orden')
            ->select('id', 'nombre', 'nombre_corto', 'activo', 'contado')
            ->get();

        foreach ($medios as $m) {
            $flags = [];
            if ($m->activo) $flags[] = 'activo';
            if ($m->contado) $flags[] = 'contado';
            $this->line("  [{$m->id}] {$m->nombre} ({$m->nombre_corto}) - " . implode(', ', $flags));
        }

        $this->line("\n--- Verificación de FKs ---");
        $tablas = [
            'tes_arrendamientos' => 'medio_de_pago',
            'tes_eventuales' => 'medio_de_pago',
            'tes_cfe_medios_pago' => 'medio_pago_tipo',
            'tes_multas_cobradas' => 'forma_pago',
        ];

        foreach ($tablas as $tabla => $campoTexto) {
            $sinFk = DB::table($tabla)
                ->whereNotNull($campoTexto)
                ->where($campoTexto, '!=', '')
                ->whereNull('medio_pago_id')
                ->count();

            $total = DB::table($tabla)->count();
            $conFk = DB::table($tabla)->whereNotNull('medio_pago_id')->count();

            $status = $sinFk === 0 ? 'OK' : 'ALERTA';
            $this->line("[{$status}] {$tabla}: {$conFk}/{$total} con FK, {$sinFk} sin FK");

            if ($sinFk > 0) {
                $errores++;
                if ($this->option('fix')) {
                    $this->fixSinFk($tabla, $campoTexto);
                }
            }
        }

        $this->line("\n--- Tabla puente de multas ---");
        $multasPuente = DB::table('tes_multa_medios_pago')->count();
        $multasCombinadas = DB::table('tes_multas_cobradas')
            ->where('forma_pago', 'like', '%/%')
            ->count();
        $this->line("Registros en puente: {$multasPuente}, Multas combinadas: {$multasCombinadas}");

        $this->line("\n--- Verificación de montos ---");
        $totalPuente = DB::table('tes_multa_medios_pago')->sum('monto');
        $multas = DB::table('tes_multas_cobradas')
            ->whereNotNull('forma_pago')
            ->get(['id', 'forma_pago']);

        $totalOriginal = 0;
        foreach ($multas as $m) {
            $pares = explode('/', $m->forma_pago);
            foreach ($pares as $par) {
                $datos = explode(':', trim($par));
                if (isset($datos[1])) {
                    $totalOriginal += (float) str_replace(',', '.', trim($datos[1]));
                }
            }
        }

        $diferencia = abs($totalPuente - $totalOriginal);
        $statusMontos = $diferencia < 0.01 ? 'OK' : 'ALERTA';
        $this->line("[{$statusMontos}] Monto puente: {$totalPuente}, Original: {$totalOriginal}, Diferencia: {$diferencia}");

        if ($diferencia >= 0.01) {
            $errores++;
        }

        $this->line("\n=== Resumen ===");
        if ($errores === 0) {
            $this->info('Todas las verificaciones pasaron correctamente.');
            Log::info('mediospago:verificar completado exitosamente');
        } else {
            $this->warn("Se encontraron {$errores} alertas. Revise los detalles arriba.");
            Log::warning("mediospago:verificar completado con {$errores} alertas");
        }

        return $errores === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function fixSinFk(string $tabla, string $campoTexto): void
    {
        $service = app(\App\Services\Tesoreria\MedioPagoService::class);
        $registros = DB::table($tabla)
            ->whereNotNull($campoTexto)
            ->where($campoTexto, '!=', '')
            ->whereNull('medio_pago_id')
            ->get(['id', $campoTexto]);

        $corregidos = 0;
        foreach ($registros as $row) {
            $medio = $service->resolverPorTexto($row->$campoTexto);
            if ($medio) {
                DB::table($tabla)->where('id', $row->id)->update(['medio_pago_id' => $medio->id]);
                $corregidos++;
            }
        }

        $this->info("  Fix: {$corregidos} registros corregidos en {$tabla}");
    }
}
