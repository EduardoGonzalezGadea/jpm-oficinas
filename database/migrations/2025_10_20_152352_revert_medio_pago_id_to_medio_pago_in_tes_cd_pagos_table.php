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
            $table->dropForeign(['medio_pago_id']);
            $table->dropColumn('medio_pago_id');
            $table->string('medio_pago')->nullable();
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
            $table->dropColumn('medio_pago');
            $table->foreignId('medio_pago_id')->nullable()->constrained('tes_medio_de_pagos');
        });
    }
};
