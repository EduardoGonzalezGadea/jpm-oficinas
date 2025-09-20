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
            $table->string('orden_cobro')->nullable();
            $table->string('numero_tramite')->nullable();
            $table->string('ingreso_contabilidad')->nullable();
            $table->decimal('monto', 10, 2);
            $table->string('titular');
            $table->string('cedula');
            $table->string('telefono')->nullable();
            $table->softDeletes();
            $table->timestamps();
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
