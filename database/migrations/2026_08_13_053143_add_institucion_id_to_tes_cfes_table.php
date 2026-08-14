<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->unsignedBigInteger('institucion_id')
                ->nullable()
                ->after('siif_distribucion_dependencia_id')
                ->comment('Institución asociada al CFE (tes_eventuales_instituciones)');

            $table->foreign('institucion_id')
                ->references('id')
                ->on('tes_eventuales_instituciones')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropForeign(['institucion_id']);
            $table->dropColumn('institucion_id');
        });
    }
};
