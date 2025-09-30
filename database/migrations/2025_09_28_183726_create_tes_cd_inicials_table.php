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
        Schema::create('tes_cd_inicials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tes_caja_diarias_id');
            $table->unsignedBigInteger('tes_denominaciones_monedas_id');
            $table->decimal('monto', 10, 2);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tes_caja_diarias_id')->references('id')->on('tes_caja_diarias');
            $table->foreign('tes_denominaciones_monedas_id')->references('id')->on('tes_denominaciones_monedas');
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
        Schema::dropIfExists('tes_cd_inicials');
    }
};