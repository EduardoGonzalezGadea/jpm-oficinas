<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_planilla_ers', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('numero');
            $table->foreignId('tipo_id')->constrained('siif_distribucion_tipos');
            $table->foreignId('dependencia_id')->constrained('siif_distribucion_dependencias');
            $table->string('turno')->nullable();
            $table->string('er_numero')->nullable();
            $table->string('egresos_numero')->nullable();
            $table->date('transferencia_fecha')->nullable();
            $table->string('confirmacion_numero')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_planilla_ers');
    }
};
