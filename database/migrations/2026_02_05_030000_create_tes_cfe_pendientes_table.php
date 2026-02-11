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
        Schema::create('tes_cfe_pendientes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_cfe', [
                'certificado_residencia',
                'multas_cobradas',
                'porte_armas',
                'tenencia_armas',
                'desconocido'
            ]);
            $table->string('serie')->nullable();
            $table->string('numero')->nullable();
            $table->date('fecha')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('moneda', 3)->default('UYU');
            $table->json('datos_extraidos'); // Datos extraidos del PDF
            $table->string('pdf_path')->nullable(); // Ruta del PDF almacenado
            $table->string('source_url')->nullable(); // URL de origen
            $table->enum('estado', ['pendiente', 'confirmado', 'rechazado', 'procesado'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->integer('user_id')->unsigned()->nullable(); // Usuario que descargó el PDF
            $table->integer('procesado_por')->unsigned()->nullable(); // Usuario que confirmó/rechazó
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('procesado_por')->references('id')->on('users');

            $table->index(['tipo_cfe', 'estado']);
            $table->index(['serie', 'numero', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_cfe_pendientes');
    }
};
