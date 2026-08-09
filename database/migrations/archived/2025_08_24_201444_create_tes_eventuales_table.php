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
        Schema::create('tes_eventuales', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('ingreso')->nullable();
            $table->enum('institucion', ['ASSE', 'INAU', 'MIDES', 'HOSPITAL CLÍNICAS', 'IMM', 'MGAP']);
            $table->string('titular')->nullable();
            $table->decimal('monto', 10, 2);
            $table->enum('medio_de_pago', ['Efectivo', 'Transferencia', 'POS', 'Cheque']);
            $table->text('detalle')->nullable();
            $table->string('orden_cobro')->nullable();
            $table->string('recibo')->nullable();
            $table->boolean('confirmado')->default(false);
            $table->foreignId('planilla_id')->nullable()->constrained('tes_eventuales_planillas');
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
        Schema::dropIfExists('tes_eventuales');
    }
};
