<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->renameColumn('confirmacion_numero', 'transferencia_confirmacion');
        });

        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->boolean('confirmada')->default(false)->after('transferencia_confirmacion');
        });
    }

    public function down()
    {
        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->dropColumn('confirmada');
        });

        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->renameColumn('transferencia_confirmacion', 'confirmacion_numero');
        });
    }
};
