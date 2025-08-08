<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_caja_arqueos', function (Blueprint $table) {
            $table->id('idArqueo');
            $table->unsignedBigInteger('relCaja');
            $table->date('fecha');
            $table->time('hora');
            $table->decimal('total_efectivo', 15, 2);
            $table->decimal('total_transferencias', 15, 2);
            $table->decimal('total_cheques', 15, 2);
            $table->decimal('diferencia', 15, 2);
            $table->json('desglose');
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('usuario_registro');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('relCaja')->references('idCaja')->on('tes_cajas');
            $table->foreign('usuario_registro')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_caja_arqueos');
    }
};
