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
        Schema::table('tes_certificados_residencia', function (Blueprint $table) {
            $table->string('numero_recibo')->nullable()->after('retira_telefono');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_certificados_residencia', function (Blueprint $table) {
            $table->dropColumn('numero_recibo');
        });
    }
};
