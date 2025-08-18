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
        Schema::create('infracciones_transito', function (Blueprint $table) {
            $table->id();
            $table->string('articulo', 10);
            $table->string('apartado', 10)->nullable();
            $table->text('descripcion');
            $table->decimal('importe_ur', 8, 1);
            $table->string('decreto', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['articulo', 'apartado']);
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('infracciones_transito');
    }
};
