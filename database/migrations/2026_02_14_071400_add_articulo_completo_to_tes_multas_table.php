<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration para agregar columna generada "articulo_completo" a la tabla tes_multas
 *
 * Esta columna permite búsquedas rápidas del formato "articulo.apartado" (ej: "103.2A")
 * sin necesidad de usar CONCAT en cada consulta, lo que mejora significativamente
 * el rendimiento de las búsquedas.
 */
class AddArticuloCompletoToTesMultasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Primero agregar la columna normal
        Schema::table('tes_multas', function (Blueprint $table) {
            $table->string('articulo_completo', 20)->nullable()->after('apartado');
        });

        // Actualizar registros existentes con el valor calculado
        DB::statement("UPDATE tes_multas SET articulo_completo = CONCAT(articulo, '.', IFNULL(apartado, ''))");

        // Agregar índice para búsquedas rápidas
        DB::statement('CREATE INDEX idx_multas_articulo_completo ON tes_multas(articulo_completo)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_multas', function (Blueprint $table) {
            $table->dropIndex('idx_multas_articulo_completo');
            $table->dropColumn('articulo_completo');
        });
    }
}
