<?php
// database/migrations/2025_10_28_000003_create_tes_cheques_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tes_cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_bancaria_id')->constrained('tes_cuentas_bancarias')->onDelete('cascade');
            $table->string('serie', 11)->nullable();
            $table->string('numero_cheque', 20);
            $table->string('documento_tipo', 255)->nullable();
            $table->string('documento_numero', 255)->nullable();
            $table->date('fecha_emision')->nullable();
            $table->unsignedInteger('emitido_por')->nullable();
            $table->string('beneficiario', 150)->nullable();
            $table->decimal('monto', 15, 2)->nullable();
            $table->text('concepto')->nullable();
            $table->enum('estado', ['disponible', 'emitido', 'anulado', 'en_planilla'])->default('disponible');
            $table->unsignedBigInteger('planilla_id')->nullable();
            $table->date('fecha_anulacion')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->date('fecha_planilla_anulada')->nullable();
            $table->unsignedInteger('planilla_anulada_por')->nullable();
            $table->unsignedInteger('anulado_por')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'numero_cheque']);
            $table->foreign('planilla_anulada_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('emitido_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('anulado_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_cheques');
    }
};
