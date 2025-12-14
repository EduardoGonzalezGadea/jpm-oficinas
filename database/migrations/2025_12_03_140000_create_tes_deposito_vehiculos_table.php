<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tes_deposito_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('titular');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->string('recibo_serie');
            $table->string('recibo_numero');
            $table->date('recibo_fecha');
            $table->string('orden_cobro');
            $table->foreignId('medio_pago_id')->constrained('tes_medio_de_pagos')->onDelete('restrict');
            $table->decimal('monto', 10, 2);
            $table->text('concepto');
            $table->unsignedBigInteger('planilla_id')->nullable();
            
            // Auditoría
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->unique(['recibo_serie', 'recibo_numero'], 'unique_recibo');
            $table->index('recibo_fecha');
            $table->index('planilla_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            
            // Foreign key a planillas
            $table->foreign('planilla_id')
                  ->references('id')
                  ->on('tes_deposito_vehiculo_planillas')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tes_deposito_vehiculos');
    }
};
