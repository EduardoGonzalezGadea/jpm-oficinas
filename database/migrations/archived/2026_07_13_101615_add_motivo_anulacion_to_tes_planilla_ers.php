<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->text('motivo_anulacion')->nullable()->after('confirmada');
        });
    }

    public function down()
    {
        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->dropColumn('motivo_anulacion');
        });
    }
};
