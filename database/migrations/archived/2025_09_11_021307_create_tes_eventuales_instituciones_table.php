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
        Schema::create('tes_eventuales_instituciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insertar las instituciones existentes
        DB::table('tes_eventuales_instituciones')->insert([
            ['nombre' => 'ASSE', 'descripcion' => 'Administración de los Servicios de Salud del Estado', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'INAU', 'descripcion' => 'Instituto del Niño y Adolescente del Uruguay', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MIDES', 'descripcion' => 'Ministerio de Desarrollo Social', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'HOSPITAL CLÍNICAS', 'descripcion' => 'Hospital de Clínicas', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'IMM', 'descripcion' => 'Intendencia de Montevideo', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'MGAP', 'descripcion' => 'Ministerio de Ganadería, Agricultura y Pesca', 'activa' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_eventuales_instituciones');
    }
};