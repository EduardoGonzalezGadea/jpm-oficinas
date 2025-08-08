<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_denominaciones', function (Blueprint $table) {
            $table->id('idDenominacion');
            $table->decimal('valor', 12, 2);
            $table->enum('tipo', ['BILLETE', 'MONEDA']);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insertar denominaciones por defecto
        DB::table('tes_denominaciones')->insert([
            // Billetes
            ['valor' => 2000.00, 'tipo' => 'BILLETE', 'orden' => 1, 'created_at' => now()],
            ['valor' => 1000.00, 'tipo' => 'BILLETE', 'orden' => 2, 'created_at' => now()],
            ['valor' => 500.00, 'tipo' => 'BILLETE', 'orden' => 3, 'created_at' => now()],
            ['valor' => 200.00, 'tipo' => 'BILLETE', 'orden' => 4, 'created_at' => now()],
            ['valor' => 100.00, 'tipo' => 'BILLETE', 'orden' => 5, 'created_at' => now()],
            ['valor' => 50.00, 'tipo' => 'BILLETE', 'orden' => 6, 'created_at' => now()],
            ['valor' => 20.00, 'tipo' => 'BILLETE', 'orden' => 7, 'created_at' => now()],
            // Monedas
            ['valor' => 50.00, 'tipo' => 'MONEDA', 'orden' => 8, 'created_at' => now()],
            ['valor' => 20.00, 'tipo' => 'MONEDA', 'orden' => 9, 'created_at' => now()],
            ['valor' => 10.00, 'tipo' => 'MONEDA', 'orden' => 10, 'created_at' => now()],
            ['valor' => 2.00, 'tipo' => 'MONEDA', 'orden' => 11, 'created_at' => now()],
            ['valor' => 1.00, 'tipo' => 'MONEDA', 'orden' => 12, 'created_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tes_denominaciones');
    }
};
