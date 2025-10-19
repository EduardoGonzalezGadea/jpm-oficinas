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
        Schema::create('tes_cd_cobros_campos_valores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cobro_id');
            $table->unsignedBigInteger('campo_id');
            $table->text('valor')->nullable();
            $table->timestamps();

            $table->foreign('cobro_id')->references('id')->on('tes_cd_cobros')->onDelete('cascade');
            $table->foreign('campo_id')->references('id')->on('tes_cd_conceptos_cobro_campos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_cd_cobros_campos_valores');
    }
};