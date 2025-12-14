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
        Schema::create('tes_prendas', function (Blueprint $table) {
            $table->id();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro');
            $table->string('titular_nombre');
            $table->string('titular_cedula');
            $table->string('titular_telefono');
            $table->unsignedBigInteger('medio_pago_id');
            $table->decimal('monto', 10, 2);
            $table->string('concepto');
            $table->string('transferencia')->nullable();
            
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('medio_pago_id')->references('id')->on('tes_medio_de_pagos');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_prendas');
    }
};
