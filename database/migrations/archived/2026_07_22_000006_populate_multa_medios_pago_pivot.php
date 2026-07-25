<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $resolverMedioPagoId = function (string $nombre): ?int {
            $nombre = mb_strtolower(trim($nombre));
            $mapeo = [
                'efectivo' => 'Efectivo',
                'cheque' => 'Cheque',
                'transferencia' => 'Transferencia Bancaria',
                'transferencia bancaria' => 'Transferencia Bancaria',
                'brou' => 'Transferencia Bancaria',
                'deposito' => 'Transferencia Bancaria',
                'pos' => 'Tarjeta de Débito (POS)',
                'debito' => 'Tarjeta de Débito (POS)',
                'tarjeta' => 'Tarjeta de Débito (POS)',
            ];
            if (isset($mapeo[$nombre])) {
                return DB::table('tes_medio_de_pagos')->where('nombre', $mapeo[$nombre])->value('id');
            }
            foreach ($mapeo as $variant => $canon) {
                if (str_contains($nombre, $variant)) {
                    return DB::table('tes_medio_de_pagos')->where('nombre', $canon)->value('id');
                }
            }
            return null;
        };

        $multas = DB::table('tes_multas_cobradas')
            ->whereNotNull('forma_pago')
            ->where('forma_pago', '!=', '')
            ->get(['id', 'forma_pago']);

        $insertados = 0;
        foreach ($multas as $multa) {
            $pares = explode('/', $multa->forma_pago);
            foreach ($pares as $par) {
                $par = trim($par);
                if (empty($par)) continue;

                $datos = explode(':', $par);
                $nombre = trim($datos[0]);
                $monto = isset($datos[1]) ? (float) str_replace(',', '.', trim($datos[1])) : 0.00;

                $medioPagoId = $resolverMedioPagoId($nombre);
                if ($medioPagoId === null) {
                    Log::warning("MigracionMedios: tes_multas_cobradas id={$multa->id} nombre='{$nombre}' sin mapeo en pivot");
                    continue;
                }

                $existe = DB::table('tes_multa_medios_pago')
                    ->where('multa_id', $multa->id)
                    ->where('medio_pago_id', $medioPagoId)
                    ->exists();

                if (!$existe) {
                    DB::table('tes_multa_medios_pago')->insert([
                        'multa_id' => $multa->id,
                        'medio_pago_id' => $medioPagoId,
                        'monto' => $monto,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $insertados++;
                } else {
                    DB::table('tes_multa_medios_pago')
                        ->where('multa_id', $multa->id)
                        ->where('medio_pago_id', $medioPagoId)
                        ->update(['monto' => DB::raw("monto + {$monto}")]);
                }
            }

            $medioPrincipalId = DB::table('tes_multa_medios_pago')
                ->where('multa_id', $multa->id)
                ->orderBy('monto', 'desc')
                ->value('medio_pago_id');

            if ($medioPrincipalId) {
                DB::table('tes_multas_cobradas')
                    ->where('id', $multa->id)
                    ->update(['medio_pago_id' => $medioPrincipalId]);
            }
        }

        Log::info("MigracionMedios: tes_multa_medios_pago poblada con {$insertados} registros");
    }

    public function down(): void
    {
        DB::table('tes_multa_medios_pago')->truncate();
        DB::table('tes_multas_cobradas')
            ->whereNotNull('forma_pago')
            ->update(['medio_pago_id' => null]);
    }
};
