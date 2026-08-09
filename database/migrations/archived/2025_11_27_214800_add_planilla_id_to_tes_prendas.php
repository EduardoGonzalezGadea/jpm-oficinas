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
        Schema::table('tes_prendas', function (Blueprint $table) {
            $table->unsignedBigInteger('planilla_id')->nullable()->after('id');
            $table->foreign('planilla_id')->references('id')->on('tes_prendas_planillas')->onDelete('set null');
            $table->index('planilla_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tes_prendas', function (Blueprint $table) {
            $table->dropForeign(['planilla_id']);
            $table->dropIndex(['planilla_id']);
            $table->dropColumn('planilla_id');
        });
    }
};
