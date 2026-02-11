<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración agrega la columna deleted_at a la tabla tes_eventuales_instituciones
     * para soportar SoftDeletes, ya que el modelo EventualInstitucion usa este trait.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tes_eventuales_instituciones', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_eventuales_instituciones', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
