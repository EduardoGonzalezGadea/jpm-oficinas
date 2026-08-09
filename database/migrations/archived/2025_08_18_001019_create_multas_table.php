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
        Schema::create('tes_multas', function (Blueprint $table) {
            $table->id();
            $table->integer('articulo');
            $table->string('apartado', 10)->nullable();
            $table->text('descripcion');
            $table->string('moneda', 3)->default('UR');

            // --- REFACTORIZADO ---
            // Se renombra 'importe' a 'importe_original' para mayor claridad.
            $table->decimal('importe_original', 10, 2);
            // Se añade el nuevo campo para el valor unificado. Es nullable porque no todas las multas tienen un nuevo valor.
            $table->decimal('importe_unificado', 10, 2)->nullable();
            // ---------------------

            $table->string('decreto', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['articulo', 'apartado']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_multas');
    }
};
