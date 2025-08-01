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
        Schema::create('tes_val_usos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conceptos_id')->constrained('tes_val_conceptos')->onDelete('cascade');
            $table->integer('desde')->comment('Nro. del primer recibo disponible');
            $table->integer('hasta')->comment('Nro. del último recibo disponible');
            $table->integer('recibos_disponibles')->comment('Cantidad actual de recibos disponibles');
            $table->string('interno', 50)->nullable()->comment('Número interno de libreta');
            $table->date('fecha_asignacion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conceptos_id', 'activo']);
            $table->index(['desde', 'hasta']);
            $table->index(['activo']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_val_usos');
    }
};
