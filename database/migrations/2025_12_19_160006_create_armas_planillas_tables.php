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
        // Porte de Armas Planillas
        Schema::create('tes_porte_armas_planillas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('numero')->unique();
            $table->timestamp('anulada_fecha')->nullable();
            $table->unsignedInteger('anulada_user_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();

            $table->foreign('anulada_user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('tes_porte_armas', function (Blueprint $table) {
            $table->unsignedBigInteger('planilla_id')->nullable()->after('id');
            $table->foreign('planilla_id')->references('id')->on('tes_porte_armas_planillas');
        });

        // Tenencia de Armas Planillas
        Schema::create('tes_tenencia_armas_planillas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('numero')->unique();
            $table->timestamp('anulada_fecha')->nullable();
            $table->unsignedInteger('anulada_user_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();

            $table->foreign('anulada_user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('tes_tenencia_armas', function (Blueprint $table) {
            $table->unsignedBigInteger('planilla_id')->nullable()->after('id');
            $table->foreign('planilla_id')->references('id')->on('tes_tenencia_armas_planillas');
        });
    }

    public function down()
    {
        Schema::table('tes_tenencia_armas', function (Blueprint $table) {
            $table->dropForeign(['planilla_id']);
            $table->dropColumn('planilla_id');
        });
        Schema::dropIfExists('tes_tenencia_armas_planillas');

        Schema::table('tes_porte_armas', function (Blueprint $table) {
            $table->dropForeign(['planilla_id']);
            $table->dropColumn('planilla_id');
        });
        Schema::dropIfExists('tes_porte_armas_planillas');
    }
};
