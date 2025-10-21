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
            $table->foreignId('concepto_pago_id')->nullable()->constrained('tes_cd_conceptos_pago');
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
            if (Schema::hasColumn('tes_cd_pagos', 'concepto_pago_id')) {
                $table->dropForeign(['concepto_pago_id']);
                $table->dropColumn('concepto_pago_id');
            }
        });
    }
};
