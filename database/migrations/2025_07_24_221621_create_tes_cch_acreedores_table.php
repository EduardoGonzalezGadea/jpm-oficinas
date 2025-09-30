<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_cch_acreedores', function (Blueprint $table) {
            $table->id('idAcreedores');
            $table->string('acreedor', 255);
            $table->timestamps();
            $table->softDeletes(); // <-- Agregado
        });

        // Insertar acreedores por defecto
        DB::table('tes_cch_acreedores')->insert([
            ['acreedor' => 'Kiosco Sire', 'created_at' => now()],
            ['acreedor' => 'I.M.P.O.', 'created_at' => now()],
            ['acreedor' => 'Nuevo Siglo', 'created_at' => now()],
            ['acreedor' => 'I.M.M.', 'created_at' => now()],
            ['acreedor' => 'Viáticos Externos', 'created_at' => now()],
            ['acreedor' => 'TV Cable', 'created_at' => now()],
            ['acreedor' => 'Diarios y revistas', 'created_at' => now()],
            ['acreedor' => 'Pagos varios', 'created_at' => now()],
            ['acreedor' => 'B.S.E.', 'created_at' => now()],
            ['acreedor' => 'Banco de Seguros del Estado', 'created_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tes_cch_acreedores');
    }
};
