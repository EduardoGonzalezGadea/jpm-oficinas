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
        Schema::create('tes_valores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->integer('recibos')->comment('Cantidad de recibos por libreta');
            $table->enum('tipo_valor', ['pesos', 'UR', 'SVE'])->default('pesos')
                ->comment('pesos=valor en pesos, UR=unidad reajustable, SVE=sin valor escrito');
            $table->decimal('valor', 12, 2)->nullable()
                ->comment('Valor escrito en la libreta en pesos (null para SVE)');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activo']);
            $table->index(['tipo_valor']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_valores');
    }
};
