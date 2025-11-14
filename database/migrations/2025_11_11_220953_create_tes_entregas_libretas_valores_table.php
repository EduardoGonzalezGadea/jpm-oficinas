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
        Schema::create('tes_entregas_libretas_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libreta_valor_id')->constrained('tes_libretas_valores');
            $table->foreignId('servicio_id')->constrained('tes_servicios');
            $table->string('numero_recibo_entrega');
            $table->date('fecha_entrega');
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('activo'); // activo, anulado
            $table->timestamps();
            $table->softDeletes();

            // Audit fields
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->integer('deleted_by')->unsigned()->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraint for numero_recibo_entrega
            $table->unique('numero_recibo_entrega');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_entregas_libretas_valores');
    }
};
