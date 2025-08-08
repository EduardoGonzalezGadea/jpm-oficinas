<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_caja_movimientos', function (Blueprint $table) {
            $table->id('idMovimiento');
            $table->unsignedBigInteger('relCaja');
            $table->date('fecha');
            $table->time('hora');
            $table->string('tipo_movimiento', 20); // INGRESO, EGRESO
            $table->text('concepto');
            $table->decimal('monto', 15, 2);
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
    }
};
