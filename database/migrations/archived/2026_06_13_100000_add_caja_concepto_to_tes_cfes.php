<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->unsignedBigInteger('tes_caja_concepto_id')
                  ->nullable()
                  ->after('archivo_pdf_path');

            $table->foreign('tes_caja_concepto_id')
                  ->references('id')
                  ->on('tes_caja_conceptos')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropForeign(['tes_caja_concepto_id']);
            $table->dropColumn('tes_caja_concepto_id');
        });
    }
};
