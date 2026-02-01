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
        Schema::create('tes_multas_cobradas', function (Blueprint $table) {
            $table->id();
            $table->string('recibo');
            $table->string('cedula')->nullable();
            $table->string('nombre')->nullable();
            $table->string('domicilio')->nullable();
            $table->string('adicional')->nullable();
            $table->date('fecha');
            $table->decimal('monto', 15, 2);
            $table->text('referencias')->nullable();
            $table->text('adenda')->nullable();
            $table->unsignedBigInteger('multas_items_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('multas_items_id')->references('id')->on('tes_multas_items');
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
        Schema::dropIfExists('tes_multas_cobradas');
    }
};
