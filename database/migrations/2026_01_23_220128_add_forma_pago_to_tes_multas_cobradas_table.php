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
        Schema::table('tes_multas_cobradas', function (Blueprint $table) {
            $table->text('forma_pago')->default('SIN DATOS')->after('monto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_multas_cobradas', function (Blueprint $table) {
            $table->dropColumn('forma_pago');
        });
    }
};
