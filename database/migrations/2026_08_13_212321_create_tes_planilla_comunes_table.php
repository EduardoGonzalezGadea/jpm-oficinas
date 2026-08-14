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
        Schema::create('tes_planilla_comunes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('numero', 100)->unique();
            $table->unsignedBigInteger('tes_caja_concepto_id');
            $table->boolean('confirmada')->default(false);
            $table->text('motivo_anulacion')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tes_caja_concepto_id')->references('id')->on('tes_caja_conceptos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tes_planilla_comunes');
    }
};
