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
        Schema::table('tes_porte_armas', function (Blueprint $table) {
            $table->string('recibo')->nullable()->after('ingreso_contabilidad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_porte_armas', function (Blueprint $table) {
            $table->dropColumn('recibo');
        });
    }
};
