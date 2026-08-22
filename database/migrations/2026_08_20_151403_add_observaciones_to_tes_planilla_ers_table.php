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
        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('ingresos_numero');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
