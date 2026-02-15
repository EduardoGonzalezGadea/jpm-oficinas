<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Índice compuesto para ordenamiento principal
        Schema::table('tes_multas', function (Blueprint $table) {
            $table->index(['articulo', 'apartado'], 'idx_multas_articulo_apartado');
        });

        // Índice para búsqueda por descripción (longitud limitada para MySQL)
        DB::statement('CREATE INDEX idx_multas_descripcion ON tes_multas(descripcion(50))');

        // Índice para soft deletes
        Schema::table('tes_multas', function (Blueprint $table) {
            $table->index('deleted_at', 'idx_multas_deleted_at');
        });

        // Índice para ordenamiento por importe
        Schema::table('tes_multas', function (Blueprint $table) {
            $table->index('importe_original', 'idx_multas_importe_original');
            $table->index('importe_unificado', 'idx_multas_importe_unificado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_multas', function (Blueprint $table) {
            $table->dropIndex('idx_multas_articulo_apartado');
            $table->dropIndex('idx_multas_descripcion');
            $table->dropIndex('idx_multas_deleted_at');
            $table->dropIndex('idx_multas_importe_original');
            $table->dropIndex('idx_multas_importe_unificado');
        });
    }
};
