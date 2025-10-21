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
        // Primero nos aseguramos de que la tabla no exista
        Schema::dropIfExists('tes_cd_conceptos_pago');

        Schema::create('tes_cd_conceptos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'conceptos_pago_created_by_foreign')->references('id')->on('users');
            $table->foreign('updated_by', 'conceptos_pago_updated_by_foreign')->references('id')->on('users');
            $table->foreign('deleted_by', 'conceptos_pago_deleted_by_foreign')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('tes_cd_conceptos_pago')) {
            // Primero nos aseguramos de eliminar todas las referencias desde la tabla de pagos
            Schema::dropIfExists('tes_cd_pagos');

            Schema::table('tes_cd_conceptos_pago', function (Blueprint $table) {
                // Eliminamos las claves foráneas si existen
                if (Schema::hasColumn('tes_cd_conceptos_pago', 'created_by')) {
                    $table->dropForeign('conceptos_pago_created_by_foreign');
                }
                if (Schema::hasColumn('tes_cd_conceptos_pago', 'updated_by')) {
                    $table->dropForeign('conceptos_pago_updated_by_foreign');
                }
                if (Schema::hasColumn('tes_cd_conceptos_pago', 'deleted_by')) {
                    $table->dropForeign('conceptos_pago_deleted_by_foreign');
                }
            });
        }
        Schema::dropIfExists('tes_cd_conceptos_pago');
    }
};
