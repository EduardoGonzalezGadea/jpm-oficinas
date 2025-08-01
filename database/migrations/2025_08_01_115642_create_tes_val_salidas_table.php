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
        Schema::create('tes_val_salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valores_id')->constrained('tes_valores')->onDelete('cascade');
            $table->foreignId('conceptos_id')->constrained('tes_val_conceptos')->onDelete('cascade');
            $table->date('fecha');
            $table->string('comprobante', 50)->comment('Nro. de recibo de entrega');
            $table->integer('desde')->comment('Número del primer recibo');
            $table->integer('hasta')->comment('Número del último recibo');
            $table->string('interno', 50)->nullable()->comment('Número interno de libreta');
            $table->integer('cantidad_libretas')->comment('Cantidad de libretas entregadas');
            $table->integer('total_recibos')->comment('Total de recibos entregados');
            $table->string('responsable', 100)->nullable()->comment('Persona responsable del retiro');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['valores_id', 'fecha']);
            $table->index(['conceptos_id', 'fecha']);
            $table->index(['comprobante']);
            $table->index(['desde', 'hasta']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_val_salidas');
    }
};
