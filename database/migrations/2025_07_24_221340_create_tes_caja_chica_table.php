<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tes_caja_chica', function (Blueprint $table) {
            $table->id('idCajaChica');
            $table->string('mes', 20);
            $table->integer('anio');
            $table->decimal('montoCajaChica', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes(); // <-- Agregado
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_caja_chica');
    }
};
