<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cch_movimientos', function (Blueprint $table) {
            $table->id('idMovimientos');
            $table->unsignedBigInteger('relPendiente'); // FK a tes_cch_pendientes.idPendientes
            $table->date('fechaMovimientos');
            $table->string('documentos', 255)->nullable();
            $table->decimal('rendido', 15, 2)->default(0);
            $table->decimal('reintegrado', 15, 2)->default(0);
            $table->decimal('recuperado', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes(); // <-- Agregado

            $table->foreign('relPendiente')->references('idPendientes')->on('tes_cch_pendientes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cch_movimientos');
    }
};
