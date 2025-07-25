<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down()
    {
        Schema::dropIfExists('tes_cch_acreedores');
    }
};
