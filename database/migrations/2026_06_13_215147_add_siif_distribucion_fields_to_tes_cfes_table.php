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
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->unsignedBigInteger('siif_distribucion_tipo_id')->nullable()->after('tes_caja_concepto_id');
            $table->unsignedBigInteger('siif_distribucion_dependencia_id')->nullable()->after('siif_distribucion_tipo_id');

            $table->foreign('siif_distribucion_tipo_id')
                  ->references('id')
                  ->on('siif_distribucion_tipos')
                  ->onDelete('set null');

            $table->foreign('siif_distribucion_dependencia_id')
                  ->references('id')
                  ->on('siif_distribucion_dependencias')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropForeign(['siif_distribucion_dependencia_id']);
            $table->dropForeign(['siif_distribucion_tipo_id']);
            $table->dropColumn(['siif_distribucion_tipo_id', 'siif_distribucion_dependencia_id']);
        });
    }
};
