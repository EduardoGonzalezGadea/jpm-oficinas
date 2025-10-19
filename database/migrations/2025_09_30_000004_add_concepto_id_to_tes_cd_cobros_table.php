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
        Schema::table('tes_cd_cobros', function (Blueprint $table) {
            $table->unsignedBigInteger('concepto_id')->nullable()->after('recibo');
            $table->foreign('concepto_id')->references('id')->on('tes_cd_conceptos_cobro');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cd_cobros', function (Blueprint $table) {
            $table->dropForeign(['concepto_id']);
            $table->dropColumn('concepto_id');
        });
    }
};