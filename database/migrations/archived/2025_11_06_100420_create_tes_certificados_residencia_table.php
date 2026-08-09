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
        Schema::create('tes_certificados_residencia', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_recibido');
            $table->integer('receptor_id')->unsigned();
            $table->foreign('receptor_id')->references('id')->on('users');
            $table->string('titular_nombre');
            $table->string('titular_apellido');
            $table->enum('titular_tipo_documento', ['Cédula', 'Cédula Extranjera', 'Pasaporte', 'Otro']);
            $table->string('titular_nro_documento');
            $table->date('fecha_entregado')->nullable();
            $table->integer('entregador_id')->unsigned()->nullable();
            $table->foreign('entregador_id')->references('id')->on('users');
            $table->string('retira_nombre')->nullable();
            $table->string('retira_apellido')->nullable();
            $table->enum('retira_tipo_documento', ['Cédula', 'Cédula Extranjera', 'Pasaporte', 'Otro'])->nullable();
            $table->string('retira_nro_documento')->nullable();
            $table->string('retira_telefono')->nullable();
            $table->date('fecha_devuelto')->nullable();
            $table->integer('devolucion_user_id')->unsigned()->nullable();
            $table->foreign('devolucion_user_id')->references('id')->on('users');
            $table->enum('estado', ['Recibido', 'Entregado', 'Devuelto'])->default('Recibido');
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
        Schema::dropIfExists('tes_certificados_residencia');
    }
};