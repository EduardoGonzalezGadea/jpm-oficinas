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
        Schema::table('tes_deposito_vehiculos', function (Blueprint $table) {
            $table->string('orden_cobro')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_deposito_vehiculos', function (Blueprint $table) {
            $table->string('orden_cobro')->nullable(false)->change();
        });
    }
};
