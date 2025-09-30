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
        Schema::create('tes_tenencia_armas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('orden_cobro');
            $table->string('numero_tramite');
            $table->string('ingreso_contabilidad');
            $table->string('recibo');
            $table->decimal('monto', 10, 2);
            $table->string('titular');
            $table->string('cedula');
            $table->string('telefono')->nullable();
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
        Schema::dropIfExists('tes_tenencia_armas');
    }
};
