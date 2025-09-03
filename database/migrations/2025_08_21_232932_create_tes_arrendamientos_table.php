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
        Schema::create('tes_arrendamientos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('ingreso')->nullable();
            $table->string('nombre')->nullable();
            $table->string('cedula')->nullable();
            $table->string('telefono')->nullable();
            $table->decimal('monto', 10, 2);
            $table->text('detalle')->nullable();
            $table->integer('orden_cobro')->nullable();
            $table->integer('recibo')->nullable();
            $table->string('medio_de_pago')->default('Transferencia')->comment('Medios de pago: Efectivo, Transferencia, POS, Cheque');
            $table->boolean('confirmado')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_arrendamientos');
    }
};
