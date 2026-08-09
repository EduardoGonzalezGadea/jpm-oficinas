<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->foreignReferences('tes_arrendamientos', 'medio_pago_id', 'tes_medio_de_pagos')) {
            Schema::table('tes_arrendamientos', function (Blueprint $t) {
                $t->foreign('medio_pago_id')
                    ->references('id')
                    ->on('tes_medio_de_pagos')
                    ->nullOnDelete();
            });
        }

        if (! $this->foreignReferences('tes_eventuales', 'medio_pago_id', 'tes_medio_de_pagos')) {
            Schema::table('tes_eventuales', function (Blueprint $t) {
                $t->foreign('medio_pago_id')
                    ->references('id')
                    ->on('tes_medio_de_pagos')
                    ->nullOnDelete();
            });
        }

        if (! $this->foreignReferences('tes_cfe_medios_pago', 'medio_pago_id', 'tes_medio_de_pagos')) {
            Schema::table('tes_cfe_medios_pago', function (Blueprint $t) {
                $t->foreign('medio_pago_id')
                    ->references('id')
                    ->on('tes_medio_de_pagos')
                    ->nullOnDelete();
            });
        }

        if (! $this->foreignReferences('tes_multas_cobradas', 'medio_pago_id', 'tes_medio_de_pagos')) {
            Schema::table('tes_multas_cobradas', function (Blueprint $t) {
                $t->foreign('medio_pago_id')
                    ->references('id')
                    ->on('tes_medio_de_pagos')
                    ->nullOnDelete();
            });
        }

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
        if (! $this->foreignReferences('tes_libro_diario', 'medio_id', 'tes_medio_de_pagos')) {
            Schema::table('tes_libro_diario', function (Blueprint $t) {
                $t->foreign('medio_id')
                    ->references('id')
                    ->on('tes_medio_de_pagos')
                    ->nullOnDelete();
            });
        }
    }

    private function foreignReferences(string $table, string $column, string $targetTable): bool
    {
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            foreach ($sm->listTableForeignKeys($table) as $fk) {
                if (in_array($column, $fk->getLocalColumns()) &&
                    $fk->getForeignTableName() === $targetTable) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
        }
        return false;
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
