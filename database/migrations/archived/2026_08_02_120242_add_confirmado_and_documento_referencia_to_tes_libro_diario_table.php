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
            $table->string('documento_referencia')->nullable()->after('cfe_id');
            $table->boolean('confirmado')->default(true)->after('documento_referencia');
            $table->timestamp('fecha_confirmacion')->nullable()->after('confirmado');
        });

        // Migrar datos existentes: todos confirmados con fecha de creación
        DB::table('tes_libro_diario')
            ->whereNull('fecha_confirmacion')
            ->update(['fecha_confirmacion' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->dropColumn(['documento_referencia', 'confirmado', 'fecha_confirmacion']);
        });
    }
};
