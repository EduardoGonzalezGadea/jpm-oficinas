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
            $table->unsignedBigInteger('planilla_comun_id')->nullable()->after('institucion_id');
            $table->foreign('planilla_comun_id')->references('id')->on('tes_planilla_comunes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropForeign(['planilla_comun_id']);
            $table->dropColumn('planilla_comun_id');
        });
    }
};
