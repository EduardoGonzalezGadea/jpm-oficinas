<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cch_dependencias', function (Blueprint $table) {
            $table->id('idDependencias');
            $table->string('dependencia', 255);
            $table->timestamps();
            $table->softDeletes();


        });

        // Insertar dependencias por defecto
        DB::table('tes_cch_dependencias')->insert([
            ['dependencia' => 'Dirección de Administración', 'created_at' => now()],
            ['dependencia' => 'Dirección de Logística y Apoyo', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona I', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona II', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona III', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona IV', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona V', 'created_at' => now()],
            ['dependencia' => 'Cuerpo de Policía de Tránsito', 'created_at' => now()],
            ['dependencia' => 'Dirección Departamental de Violencia Doméstica y de Género', 'created_at' => now()],
            ['dependencia' => 'Brigada Departamental Antidroga', 'created_at' => now()],
            ['dependencia' => 'Unidad de Análisis de Violencia en el Deporte', 'created_at' => now()],
            ['dependencia' => 'Dirección de Administración (especial)', 'created_at' => now()],
            ['dependencia' => 'Dirección de Logística y Apoyo (especial)', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona I (especial)', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona II (especial)', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona III (especial)', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona IV (especial)', 'created_at' => now()],
            ['dependencia' => 'U.R.P.M. Zona V (especial)', 'created_at' => now()],
            ['dependencia' => 'Cuerpo de Policía de Tránsito (especial)', 'created_at' => now()],
            ['dependencia' => 'Dirección Departamental de Violencia Doméstica y de Género (especial)', 'created_at' => now()],
            ['dependencia' => 'Brigada Departamental Antidroga (especial)', 'created_at' => now()],
            ['dependencia' => 'Unidad de Análisis de Violencia en el Deporte (especial)', 'created_at' => now()],
            ['dependencia' => 'Brigada de Investigación Departamental de Delitos Automotores', 'created_at' => now()],
            ['dependencia' => 'Dirección de Tesorería', 'created_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tes_cch_dependencias');
    }
};
