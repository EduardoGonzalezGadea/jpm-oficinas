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
        Schema::create('tes_servicio_tipo_libreta', function (Blueprint $table) {
            $table->unsignedBigInteger('servicio_id');
            $table->unsignedBigInteger('tipo_libreta_id');

            $table->foreign('servicio_id')->references('id')->on('tes_servicios')->onDelete('cascade');
            $table->foreign('tipo_libreta_id')->references('id')->on('tes_tipos_libretas')->onDelete('cascade');

            $table->primary(['servicio_id', 'tipo_libreta_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_servicio_tipo_libreta');
    }
};
