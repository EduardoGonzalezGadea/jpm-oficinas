<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->string('clave', 50)->unique()->nullable()->after('nombre');
        });

        // Los datos de clave se insertan en ModuloSeeder
    }

    public function down()
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn('clave');
        });
    }
};
