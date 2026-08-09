<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tes_cajas_aperturas')) {
            Schema::create('tes_cajas_aperturas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cajero_id');
                $table->date('fecha_apertura');
                $table->time('hora_apertura');
                $table->decimal('saldo_inicial', 12, 2)->default(0);
                $table->decimal('saldo_cierre', 12, 2)->nullable();
                $table->timestamp('fecha_cierre')->nullable();
                $table->string('estado', 20)->default('abierta');
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('cajero_id');
                $table->index('estado');
                $table->index('created_by');
                $table->index('updated_by');
                $table->index('deleted_by');
            });
        }

        if (!Schema::hasTable('tes_cajas_movimientos')) {
            Schema::create('tes_cajas_movimientos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('caja_apertura_id');
                $table->string('tipo_movimiento', 20);
                $table->decimal('monto', 12, 2);
                $table->unsignedBigInteger('medio_pago_id')->nullable();
                $table->unsignedBigInteger('cfe_id')->nullable();
                $table->unsignedBigInteger('libro_diario_id')->nullable();
                $table->string('concepto')->nullable();
                $table->text('descripcion')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('caja_apertura_id');
                $table->index('libro_diario_id');
                $table->index('cfe_id');
                $table->index('created_by');

                $table->foreign('caja_apertura_id')
                    ->references('id')->on('tes_cajas_aperturas')
                    ->onDelete('cascade');
                $table->foreign('libro_diario_id')
                    ->references('id')->on('tes_libro_diario')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('tes_cajas_arqueos')) {
            Schema::create('tes_cajas_arqueos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('caja_apertura_id');
                $table->decimal('total_efectivo', 12, 2)->default(0);
                $table->decimal('total_transferencias', 12, 2)->default(0);
                $table->decimal('total_cheques', 12, 2)->default(0);
                $table->decimal('diferencia', 12, 2)->default(0);
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->timestamps();

                $table->index('caja_apertura_id');
                $table->index('usuario_id');
            });
        }

        if (!Schema::hasTable('tes_cajas_desgloses')) {
            Schema::create('tes_cajas_desgloses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('caja_apertura_id')->nullable();
                $table->unsignedBigInteger('arqueo_id')->nullable();
                $table->unsignedBigInteger('tes_discriminacion_monetaria_id');
                $table->integer('cantidad')->default(0);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->string('tipo_referencia')->nullable();
                $table->timestamps();

                $table->index('caja_apertura_id');
                $table->index('arqueo_id');
                $table->index('tes_discriminacion_monetaria_id');
            });
        }

        if (!Schema::hasTable('tes_cajas_conceptos')) {
            Schema::create('tes_cajas_conceptos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('tipo', 20)->default('AMBOS');
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_cajas_desgloses');
        Schema::dropIfExists('tes_cajas_arqueos');
        Schema::dropIfExists('tes_cajas_movimientos');
        Schema::dropIfExists('tes_cajas_aperturas');
        Schema::dropIfExists('tes_cajas_conceptos');
    }
};
