<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cajas', function (Blueprint $table) {
            $table->id('idCaja');
            $table->date('fecha_apertura');
            $table->time('hora_apertura');
            $table->decimal('saldo_inicial', 15, 2);
            $table->date('fecha_cierre')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->decimal('saldo_final', 15, 2)->nullable();
            $table->string('estado', 20); // ABIERTA, CERRADA
            $table->unsignedInteger('usuario_apertura');
            $table->unsignedInteger('usuario_cierre')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('usuario_apertura')->references('id')->on('users');
            $table->foreign('usuario_cierre')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cajas');
    }
};
