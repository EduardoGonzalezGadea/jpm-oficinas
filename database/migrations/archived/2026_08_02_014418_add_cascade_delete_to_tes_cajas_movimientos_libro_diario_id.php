<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tes_cajas_movimientos')) {
            return;
        }

        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tes_cajas_movimientos' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'tes_cajas_movimientos_libro_diario_id_foreign'");

        if (!empty($foreignKeys)) {
            DB::statement('
                ALTER TABLE `tes_cajas_movimientos`
                DROP FOREIGN KEY `tes_cajas_movimientos_libro_diario_id_foreign`
            ');
        }

        DB::statement('
            ALTER TABLE `tes_cajas_movimientos`
            ADD CONSTRAINT `tes_cajas_movimientos_libro_diario_id_foreign`
            FOREIGN KEY (`libro_diario_id`) REFERENCES `tes_libro_diario` (`id`)
            ON DELETE CASCADE
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tes_cajas_movimientos')) {
            return;
        }

        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tes_cajas_movimientos' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'tes_cajas_movimientos_libro_diario_id_foreign'");

        if (!empty($foreignKeys)) {
            DB::statement('
                ALTER TABLE `tes_cajas_movimientos`
                DROP FOREIGN KEY `tes_cajas_movimientos_libro_diario_id_foreign`
            ');
        }

        DB::statement('
            ALTER TABLE `tes_cajas_movimientos`
            ADD CONSTRAINT `tes_cajas_movimientos_libro_diario_id_foreign`
            FOREIGN KEY (`libro_diario_id`) REFERENCES `tes_libro_diario` (`id`)
            ON DELETE RESTRICT
        ');
    }
};