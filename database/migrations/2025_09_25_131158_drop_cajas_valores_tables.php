<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Desactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Eliminar tablas de cajas
        Schema::dropIfExists('tes_caja_movimientos');
        Schema::dropIfExists('tes_caja_arqueos');
        Schema::dropIfExists('tes_caja_denominaciones');
        Schema::dropIfExists('tes_caja_conceptos');
        Schema::dropIfExists('tes_cajas');

        // Eliminar tablas de valores
        Schema::dropIfExists('tes_valores_salidas');
        Schema::dropIfExists('tes_valores_entradas');
        Schema::dropIfExists('tes_valores_conceptos');
        Schema::dropIfExists('tes_valores');

        // Reactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Esta migración es irreversible ya que no tenemos la estructura original de las tablas
        throw new \Exception('Esta migración no se puede revertir');
    }
};
