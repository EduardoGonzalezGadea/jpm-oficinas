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
        Schema::create('siif_distribucions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_id')->constrained('siif_distribucion_tipos');
            $table->foreignId('dependencia_id')->constrained('siif_distribucion_dependencias');
            $table->string('rubro')->nullable();
            $table->string('sub_rubro')->nullable();
            $table->string('recurso')->nullable();
            $table->string('concepto')->nullable();
            $table->string('codigo_sir')->nullable();
            $table->decimal('porcentaje', 6, 3);
            $table->string('financiacion')->nullable();
            $table->string('inciso')->nullable();
            $table->string('unidad_ejecutora')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('siif_distribucions');
    }
};
