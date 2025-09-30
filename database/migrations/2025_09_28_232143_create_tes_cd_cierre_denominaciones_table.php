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
        Schema::create('tes_cd_cierre_denominaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tes_cd_cierres_id');
            $table->unsignedBigInteger('tes_denominaciones_monedas_id');
            $table->foreign('tes_cd_cierres_id', 'fk_tes_cd_cierre_id')->references('id')->on('tes_cd_cierres');
            $table->foreign('tes_denominaciones_monedas_id', 'fk_tes_cd_cierre_den_moneda')->references('id')->on('tes_denominaciones_monedas');
            $table->decimal('monto', 18, 2);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_cd_cierre_denominaciones');
    }
};
