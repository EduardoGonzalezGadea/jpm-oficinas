<?php
// database/migrations/2025_10_28_000004_create_tes_planillas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tes_planillas_cheques', function (Blueprint $table) {
            $table->id();
            $table->string('numero_planilla', 20)->unique();
            $table->date('fecha_generacion');
            $table->enum('estado', ['generada', 'anulada'])->default('generada');
            $table->date('fecha_anulacion')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->unsignedInteger('generada_por');
            $table->unsignedInteger('anulada_por')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('generada_por')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('anulada_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tes_planillas_cheques');
    }
};
