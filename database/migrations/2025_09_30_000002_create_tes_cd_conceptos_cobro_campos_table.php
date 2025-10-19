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
        Schema::create('tes_cd_conceptos_cobro_campos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('concepto_id');
            $table->string('nombre');
            $table->string('titulo')->nullable();
            $table->enum('tipo', ['text', 'number', 'date', 'select', 'textarea', 'checkbox']);
            $table->boolean('requerido')->default(false);
            $table->json('opciones')->nullable(); // Para select: array de opciones
            $table->integer('orden')->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('concepto_id')->references('id')->on('tes_cd_conceptos_cobro')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_cd_conceptos_cobro_campos');
    }
};
