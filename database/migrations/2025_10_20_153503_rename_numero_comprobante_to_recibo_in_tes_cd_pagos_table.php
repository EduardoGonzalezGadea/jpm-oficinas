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
        Schema::table('tes_cd_pagos', function (Blueprint $table) {
            $table->renameColumn('numero_comprobante', 'recibo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cd_pagos', function (Blueprint $table) {
            $table->renameColumn('recibo', 'numero_comprobante');
        });
    }
};
