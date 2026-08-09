<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tes_multa_medios_pago')) {
            return;
        }
        Schema::create('tes_multa_medios_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('multa_id');
            $table->unsignedBigInteger('medio_pago_id');
            $table->decimal('monto', 12, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('multa_id')
                ->references('id')
                ->on('tes_multas_cobradas')
                ->onDelete('cascade');

            $table->foreign('medio_pago_id')
                ->references('id')
                ->on('tes_medio_de_pagos');

            $table->unique(['multa_id', 'medio_pago_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_multa_medios_pago');
    }
};
