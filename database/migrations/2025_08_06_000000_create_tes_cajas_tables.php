<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('tes_caja_movimientos');
        Schema::dropIfExists('tes_cajas');

        Schema::create('tes_cajas', function (Blueprint $table) {
            $table->id('idCaja');
            $table->date('fecha_apertura');
            $table->time('hora_apertura');
            $table->decimal('saldo_inicial', 12, 2);
            $table->date('fecha_cierre')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->decimal('saldo_final', 12, 2)->nullable();
            $table->string('estado', 20); // ABIERTA, CERRADA
            $table->unsignedInteger('usuario_apertura');
            $table->unsignedInteger('usuario_cierre')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('usuario_apertura')->references('id')->on('users');
            $table->foreign('usuario_cierre')->references('id')->on('users');
        });

        Schema::create('tes_caja_movimientos', function (Blueprint $table) {
            $table->id('idMovimiento');
            $table->unsignedBigInteger('relCaja');
            $table->date('fecha');
            $table->time('hora');
            $table->string('tipo_movimiento', 20); // INGRESO, EGRESO
            $table->text('concepto');
            $table->decimal('monto', 12, 2);
            $table->string('forma_pago', 20); // EFECTIVO, TRANSFERENCIA, CHEQUE
            $table->string('referencia')->nullable();
            $table->unsignedInteger('usuario_registro');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('relCaja')->references('idCaja')->on('tes_cajas');
            $table->foreign('usuario_registro')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_caja_movimientos');
        Schema::dropIfExists('tes_cajas');
    }
};
