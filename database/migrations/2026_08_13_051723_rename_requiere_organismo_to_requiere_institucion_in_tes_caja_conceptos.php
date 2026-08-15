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
        // Laravel 11: renameColumn() con Doctrine DBAL tiene bugs
        // Usamos SQL directo en su lugar
        // Solo ejecutar si la columna requiere_organismo existe
        if (Schema::hasColumn('tes_caja_conceptos', 'requiere_organismo')) {
            DB::statement('ALTER TABLE tes_caja_conceptos CHANGE COLUMN requiere_organismo requiere_institucion BOOLEAN NOT NULL DEFAULT 0');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tes_caja_conceptos', 'requiere_institucion')) {
            DB::statement('ALTER TABLE tes_caja_conceptos CHANGE COLUMN requiere_institucion requiere_organismo BOOLEAN NOT NULL DEFAULT 0');
        }
    }
};
