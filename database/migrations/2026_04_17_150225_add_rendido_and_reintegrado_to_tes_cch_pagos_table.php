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
        Schema::table('tes_cch_pagos', function (Blueprint $table) {
            $table->decimal('rendidoPagos', 15, 2)->nullable()->after('montoPagos');
            $table->decimal('reintegradoPagos', 15, 2)->nullable()->after('rendidoPagos');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cch_pagos', function (Blueprint $table) {
            $table->dropColumn(['rendidoPagos', 'reintegradoPagos']);
        });
    }
};
