<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_caja_conceptos', function (Blueprint $table) {
            $table->unsignedBigInteger('siif_distribucion_tipo_id')
                  ->nullable()
                  ->after('permite_planilla')
                  ->comment('Tipo de distribución SIIF asociado a este concepto');

            $table->foreign('siif_distribucion_tipo_id')
                  ->references('id')
                  ->on('siif_distribucion_tipos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tes_caja_conceptos', function (Blueprint $table) {
            $table->dropForeign(['siif_distribucion_tipo_id']);
            $table->dropColumn('siif_distribucion_tipo_id');
        });
    }
};
