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
        Schema::create('tes_libretas_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_libreta_id')->constrained('tes_tipos_libretas');
            $table->string('serie')->nullable();
            $table->integer('numero_inicial');
            $table->integer('numero_final');
            $table->date('fecha_recepcion');
            $table->string('estado')->default('en_stock'); // en_stock, en_uso, finalizada, anulada
            $table->integer('proximo_recibo_disponible')->nullable();
            $table->foreignId('servicio_asignado_id')->nullable()->constrained('tes_servicios');
            $table->timestamps();
            $table->softDeletes();

            // Audit fields
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->integer('deleted_by')->unsigned()->nullable();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_libretas_valores');
    }
};
