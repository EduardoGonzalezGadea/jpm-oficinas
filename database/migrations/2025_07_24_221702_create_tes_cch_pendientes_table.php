<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cch_pendientes', function (Blueprint $table) {
            $table->id('idPendientes');
            $table->unsignedBigInteger('relCajaChica'); // FK a tes_caja_chica.idCajaChica
            $table->integer('pendiente');
            $table->date('fechaPendientes');
            $table->unsignedBigInteger('relDependencia'); // FK a tes_cch_dependencias.idDependencias
            $table->decimal('montoPendientes', 15, 2);
            $table->timestamps();
            $table->softDeletes(); // <-- Agregado

            $table->foreign('relCajaChica')->references('idCajaChica')->on('tes_caja_chica')->onDelete('cascade');
            $table->foreign('relDependencia')->references('idDependencias')->on('tes_cch_dependencias')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cch_pendientes');
    }
};
