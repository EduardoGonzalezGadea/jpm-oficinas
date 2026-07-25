<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->unsignedBigInteger('nuevo_medio_id')->nullable()->after('medio_id');
        });

        $helperNormalizar = function (string $texto): ?int {
            $texto = mb_strtolower(trim($texto));
            $mapeo = [
                'efectivo' => 'Efectivo',
                'cheque' => 'Cheque',
                'transferencia' => 'Transferencia Bancaria',
                'transferencia bancaria' => 'Transferencia Bancaria',
                'brou' => 'Transferencia Bancaria',
                'deposito' => 'Transferencia Bancaria',
                'pos' => 'Tarjeta de Débito (POS)',
                'debito' => 'Tarjeta de Débito (POS)',
                'débito' => 'Tarjeta de Débito (POS)',
                'tarjeta' => 'Tarjeta de Débito (POS)',
                'credito' => 'Tarjeta de Débito (POS)',
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
        };

        $registros = DB::table('tes_libro_diario')
            ->join('tes_lb_medios', 'tes_libro_diario.medio_id', '=', 'tes_lb_medios.id')
            ->select('tes_libro_diario.id', 'tes_lb_medios.nombre')
            ->get();

        foreach ($registros as $row) {
            $nuevoId = $helperNormalizar($row->nombre);
            if ($nuevoId) {
                DB::table('tes_libro_diario')
                    ->where('id', $row->id)
                    ->update(['nuevo_medio_id' => $nuevoId]);
            }
        }

        try {
            Schema::table('tes_libro_diario', function (Blueprint $table) {
                $table->dropForeign(['medio_id']);
            });
        } catch (\Throwable $e) {
        }

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->dropColumn('medio_id');
        });

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->renameColumn('nuevo_medio_id', 'medio_id');
        });

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->foreign('medio_id')
                ->references('id')
                ->on('tes_medio_de_pagos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->dropForeign(['medio_id']);
        });

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->renameColumn('medio_id', 'nuevo_medio_id');
        });

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->unsignedBigInteger('medio_id')->nullable()->after('nuevo_medio_id');
        });

        $registros = DB::table('tes_libro_diario')
            ->join('tes_lb_medios', 'tes_libro_diario.nuevo_medio_id', '=', 'tes_lb_medios.id')
            ->select('tes_libro_diario.id', 'tes_lb_medios.nombre')
            ->get();

        foreach ($registros as $row) {
            $lbMedio = DB::table('tes_lb_medios')->where('nombre', $row->nombre)->first();
            if ($lbMedio) {
                DB::table('tes_libro_diario')
                    ->where('id', $row->id)
                    ->update(['medio_id' => $lbMedio->id]);
            }
        }

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->dropColumn('nuevo_medio_id');
        });

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->foreign('medio_id')
                ->references('id')
                ->on('tes_lb_medios');
        });
    }
};
