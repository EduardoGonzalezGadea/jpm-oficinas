<?php
// database/migrations/2025_10_28_000002_create_tes_cuentas_bancarias_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('tes_cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_id')->constrained('tes_bancos')->onDelete('cascade');
            $table->string('numero_cuenta', 50);
            $table->string('tipo', 20); // Corriente, Ahorro, etc.
            $table->boolean('activa')->default(true);
            $table->text('observaciones')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // Insertar cuenta bancaria principal
        DB::table('tes_cuentas_bancarias')->insert([
            'banco_id' => 1,
            'numero_cuenta' => '001557032-00118',
            'tipo' => 'Corriente',
            'activa' => true,
            'observaciones' => 'CUENTA PRINCIPAL DE LA J.P.M.',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tes_cuentas_bancarias');
    }
};
