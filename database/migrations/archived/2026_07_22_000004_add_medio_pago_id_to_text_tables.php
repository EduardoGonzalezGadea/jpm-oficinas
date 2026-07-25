<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function resolverMedioPagoId(string $texto): ?int
    {
        $texto = mb_strtolower(trim($texto));
        if ($texto === '' || $texto === 'sin datos') return null;

        $mapeo = [
            'efectivo' => 'Efectivo',
            'contado' => 'Efectivo',
            'cash' => 'Efectivo',
            'cheque' => 'Cheque',
            'transferencia' => 'Transferencia Bancaria',
            'transferencia bancaria' => 'Transferencia Bancaria',
            'brou' => 'Transferencia Bancaria',
            'deposito' => 'Transferencia Bancaria',
            'depósito' => 'Transferencia Bancaria',
            'siif' => 'Transferencia Bancaria',
            'pos' => 'Tarjeta de Débito (POS)',
            'debito' => 'Tarjeta de Débito (POS)',
            'débito' => 'Tarjeta de Débito (POS)',
            'tarjeta' => 'Tarjeta de Débito (POS)',
            'tarjeta de débito' => 'Tarjeta de Débito (POS)',
            'credito' => 'Tarjeta de Débito (POS)',
            'crédito' => 'Tarjeta de Débito (POS)',
        ];

        if (isset($mapeo[$texto])) {
            return DB::table('tes_medio_de_pagos')->where('nombre', $mapeo[$texto])->value('id');
        }

        foreach ($mapeo as $variant => $canon) {
            if (str_contains($texto, $variant)) {
                return DB::table('tes_medio_de_pagos')->where('nombre', $canon)->value('id');
            }
        }
        return null;
    }

    public function up(): void
    {
        $tablasTexto = ['tes_arrendamientos', 'tes_eventuales'];

        foreach ($tablasTexto as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->unsignedBigInteger('medio_pago_id')->nullable()->after('medio_de_pago');
            });

            $campoTexto = 'medio_de_pago';
            $registros = DB::table($tabla)
                ->whereNotNull($campoTexto)
                ->where($campoTexto, '!=', '')
                ->get(['id', $campoTexto]);

            $sinMapeo = 0;
            foreach ($registros as $row) {
                $id = $this->resolverMedioPagoId($row->$campoTexto);
                if ($id) {
                    DB::table($tabla)->where('id', $row->id)->update(['medio_pago_id' => $id]);
                } else {
                    $sinMapeo++;
                    Log::warning("MigracionMedios: {$tabla} id={$row->id} texto='{$row->$campoTexto}' sin mapeo");
                }
            }

            Log::info("MigracionMedios: {$tabla} {$registros->count()} registros, {$sinMapeo} sin mapeo");
        }

        Schema::table('tes_cfe_medios_pago', function (Blueprint $t) {
            $t->unsignedBigInteger('medio_pago_id')->nullable()->after('medio_pago_tipo');
        });

        $cfeRegistros = DB::table('tes_cfe_medios_pago')
            ->whereNotNull('medio_pago_tipo')
            ->where('medio_pago_tipo', '!=', '')
            ->get(['id', 'medio_pago_tipo']);

        $cfeSinMapeo = 0;
        foreach ($cfeRegistros as $row) {
            $id = $this->resolverMedioPagoId($row->medio_pago_tipo);
            if ($id) {
                DB::table('tes_cfe_medios_pago')
                    ->where('id', $row->id)
                    ->update(['medio_pago_id' => $id]);
            } else {
                $cfeSinMapeo++;
                Log::warning("MigracionMedios: tes_cfe_medios_pago id={$row->id} tipo='{$row->medio_pago_tipo}' sin mapeo");
            }
        }
        Log::info("MigracionMedios: tes_cfe_medios_pago {$cfeRegistros->count()} registros, {$cfeSinMapeo} sin mapeo");

        Schema::table('tes_multas_cobradas', function (Blueprint $t) {
            $t->unsignedBigInteger('medio_pago_id')->nullable()->after('forma_pago');
        });

        $multasRegistros = DB::table('tes_multas_cobradas')
            ->whereNotNull('forma_pago')
            ->where('forma_pago', '!=', '')
            ->where('forma_pago', 'not like', '%/%')
            ->get(['id', 'forma_pago']);

        $multasSinMapeo = 0;
        foreach ($multasRegistros as $row) {
            $id = $this->resolverMedioPagoId($row->forma_pago);
            if ($id) {
                DB::table('tes_multas_cobradas')
                    ->where('id', $row->id)
                    ->update(['medio_pago_id' => $id]);
            } else {
                $multasSinMapeo++;
                Log::warning("MigracionMedios: tes_multas_cobradas id={$row->id} forma_pago='{$row->forma_pago}' sin mapeo");
            }
        }
        Log::info("MigracionMedios: tes_multas_cobradas simples {$multasRegistros->count()} registros, {$multasSinMapeo} sin mapeo");
    }

    public function down(): void
    {
        $tablas = ['tes_arrendamientos', 'tes_eventuales', 'tes_cfe_medios_pago', 'tes_multas_cobradas'];
        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropColumn('medio_pago_id');
            });
        }
    }
};
