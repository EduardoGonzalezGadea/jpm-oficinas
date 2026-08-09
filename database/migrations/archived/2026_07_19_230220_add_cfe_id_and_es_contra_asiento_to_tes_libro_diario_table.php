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
        if (Schema::hasTable('tes_libro_diario') && Schema::hasColumn('tes_libro_diario', 'cfe_id')) {
            return;
        }
        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->unsignedBigInteger('cfe_id')->nullable()->after('grupo_redistribucion_id');
            $table->boolean('es_contra_asiento')->default(false)->after('cfe_id');

            $table->foreign('cfe_id')
                ->references('id')
                ->on('tes_cfes')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->dropForeign(['cfe_id']);
            $table->dropColumn(['cfe_id', 'es_contra_asiento']);
        });
    }
};
