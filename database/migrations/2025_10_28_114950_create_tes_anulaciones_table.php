<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tes_anulaciones', function (Blueprint $table) {
            $table->id();
            $table->morphs('anulable'); // cheque o planilla
            $table->json('datos_originales');
            $table->text('motivo');
            $table->unsignedInteger('anulado_por');
            $table->timestamp('fecha_anulacion');
            $table->timestamps();

            $table->foreign('anulado_por')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_anulaciones');
    }
};
