<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cd_pagos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->string('medio_pago');
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cd_pagos');
    }
};
