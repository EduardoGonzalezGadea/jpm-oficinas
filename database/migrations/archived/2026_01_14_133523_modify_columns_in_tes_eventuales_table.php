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
        Schema::table('tes_eventuales', function (Blueprint $table) {
            // Cambiamos enum a string para permitir valores de las tablas auxiliares
            $table->string('institucion')->change();
            $table->string('medio_de_pago')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_eventuales', function (Blueprint $table) {
            $table->enum('institucion', ['ASSE', 'INAU', 'MIDES', 'HOSPITAL CLÍNICAS', 'IMM', 'MGAP'])->change();
            $table->enum('medio_de_pago', ['Efectivo', 'Transferencia', 'POS', 'Cheque'])->change();
        });
    }
};
