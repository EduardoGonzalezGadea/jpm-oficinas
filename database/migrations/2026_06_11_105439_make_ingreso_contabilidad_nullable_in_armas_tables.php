<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_tenencia_armas', function (Blueprint $table) {
            $table->string('ingreso_contabilidad')->nullable()->change();
        });
        Schema::table('tes_porte_armas', function (Blueprint $table) {
            $table->string('ingreso_contabilidad')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_tenencia_armas', function (Blueprint $table) {
            $table->string('ingreso_contabilidad')->nullable(false)->change();
        });
        Schema::table('tes_porte_armas', function (Blueprint $table) {
            $table->string('ingreso_contabilidad')->nullable(false)->change();
        });
    }
};
