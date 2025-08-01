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
        Schema::create('tes_val_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valores_id')->constrained('tes_valores')->onDelete('cascade');
            $table->string('concepto', 150);
            $table->decimal('monto', 12, 2);
            $table->enum('tipo_monto', ['pesos', 'UR', 'porcentaje'])->default('pesos');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['valores_id', 'activo']);
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
        Schema::dropIfExists('tes_val_conceptos');
    }
};
