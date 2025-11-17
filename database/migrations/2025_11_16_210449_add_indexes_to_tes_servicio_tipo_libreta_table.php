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
        Schema::table('tes_servicio_tipo_libreta', function (Blueprint $table) {
            $table->index('servicio_id');
            $table->index('tipo_libreta_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_servicio_tipo_libreta', function (Blueprint $table) {
            $table->dropIndex(['servicio_id']);
            $table->dropIndex(['tipo_libreta_id']);
        });
    }
};