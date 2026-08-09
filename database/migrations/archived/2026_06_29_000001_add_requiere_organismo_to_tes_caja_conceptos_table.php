<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_caja_conceptos', function (Blueprint $table) {
            $table->boolean('requiere_organismo')
                  ->default(false)
                  ->after('permite_planilla')
                  ->comment('Indica si el concepto requiere seleccionar un organismo/entidad');
        });
    }

    public function down(): void
    {
        Schema::table('tes_caja_conceptos', function (Blueprint $table) {
            $table->dropColumn('requiere_organismo');
        });
    }
};
