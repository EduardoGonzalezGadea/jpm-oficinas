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
        Schema::create('tes_tarjetas_cobro_brou', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_recibido');
            $table->integer('receptor_id')->unsigned();
            $table->foreign('receptor_id')->references('id')->on('users');
            $table->string('titular_cedula');
            $table->string('titular_nombre');
            $table->string('titular_apellido');
            $table->string('numero_tarjeta');
            $table->date('fecha_entregado')->nullable();
            $table->integer('entregador_id')->unsigned()->nullable();
            $table->foreign('entregador_id')->references('id')->on('users');
            $table->date('fecha_devuelto')->nullable();
            $table->integer('devolucion_user_id')->unsigned()->nullable();
            $table->foreign('devolucion_user_id')->references('id')->on('users');
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['Recibido', 'Entregado', 'Devuelto'])->default('Recibido');
            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->integer('updated_by')->unsigned()->nullable();
            $table->foreign('updated_by')->references('id')->on('users');
            $table->integer('deleted_by')->unsigned()->nullable();
            $table->foreign('deleted_by')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_tarjetas_cobro_brou');
    }
};