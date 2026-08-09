<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cch_pagos', function (Blueprint $table) {
            $table->id('idPagos');
            $table->unsignedBigInteger('relCajaChica_Pagos'); // FK a tes_caja_chica.idCajaChica
            $table->date('fechaEgresoPagos');
            $table->string('egresoPagos', 50)->nullable();
            $table->unsignedBigInteger('relAcreedores')->nullable(); // FK a tes_cch_acreedores.idAcreedores
            $table->string('conceptoPagos', 255);
            $table->decimal('montoPagos', 15, 2);
            $table->date('fechaIngresoPagos')->nullable();
            $table->string('ingresoPagos', 50)->nullable();
            $table->string('ingresoPagosBSE', 50)->nullable();
            $table->decimal('recuperadoPagos', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes(); // <-- Agregado

            $table->foreign('relCajaChica_Pagos')->references('idCajaChica')->on('tes_caja_chica')->onDelete('cascade');
            $table->foreign('relAcreedores')->references('idAcreedores')->on('tes_cch_acreedores')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cch_pagos');
    }
};
