<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_arrendamientos', function (Blueprint $t) {
            $t->foreign('medio_pago_id')
                ->references('id')
                ->on('tes_medio_de_pagos')
                ->nullOnDelete();
        });

        Schema::table('tes_eventuales', function (Blueprint $t) {
            $t->foreign('medio_pago_id')
                ->references('id')
                ->on('tes_medio_de_pagos')
                ->nullOnDelete();
        });

        Schema::table('tes_cfe_medios_pago', function (Blueprint $t) {
            $t->foreign('medio_pago_id')
                ->references('id')
                ->on('tes_medio_de_pagos')
                ->nullOnDelete();
        });

        Schema::table('tes_multas_cobradas', function (Blueprint $t) {
            $t->foreign('medio_pago_id')
                ->references('id')
                ->on('tes_medio_de_pagos')
                ->nullOnDelete();
        });

        try {
            Schema::table('tes_lb_medios', function (Blueprint $t) {
                $t->dropForeign(['medio_id']);
            });
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('tes_libro_diario', function (Blueprint $t) {
                $t->dropForeign(['medio_id']);
            });
        } catch (\Throwable $e) {
        }
        Schema::table('tes_libro_diario', function (Blueprint $t) {
            $t->foreign('medio_id')
                ->references('id')
                ->on('tes_medio_de_pagos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $tablas = ['tes_arrendamientos', 'tes_eventuales', 'tes_cfe_medios_pago', 'tes_multas_cobradas'];
        foreach ($tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropForeign(['medio_pago_id']);
            });
        }

        Schema::table('tes_libro_diario', function (Blueprint $t) {
            $t->dropForeign(['medio_id']);
        });
    }
};
