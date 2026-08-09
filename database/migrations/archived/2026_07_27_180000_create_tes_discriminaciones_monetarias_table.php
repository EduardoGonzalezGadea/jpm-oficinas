<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tes_discriminaciones_monetarias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50); // Billetes o Monedas
            $table->decimal('valor', 10, 2); // Valor numérico (2000, 1000, 500, etc.)
            $table->string('texto', 100); // Texto descriptivo (dos mil, mil, etc.)
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('tipo');
            $table->index('valor');
            $table->index('activo');
            $table->index(['tipo', 'valor']);
        });

        // Insertar datos iniciales
        DB::table('tes_discriminaciones_monetarias')->insert([
            // Billetes
            [
                'tipo' => 'Billetes',
                'valor' => 2000.00,
                'texto' => 'dos mil',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Billetes',
                'valor' => 1000.00,
                'texto' => 'mil',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Billetes',
                'valor' => 500.00,
                'texto' => 'quinientos',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Billetes',
                'valor' => 200.00,
                'texto' => 'doscientos',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Billetes',
                'valor' => 100.00,
                'texto' => 'cien',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Billetes',
                'valor' => 50.00,
                'texto' => 'cincuenta',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Billetes',
                'valor' => 20.00,
                'texto' => 'veinte',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Monedas
            [
                'tipo' => 'Monedas',
                'valor' => 50.00,
                'texto' => 'cincuenta',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Monedas',
                'valor' => 10.00,
                'texto' => 'diez',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Monedas',
                'valor' => 5.00,
                'texto' => 'cinco',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Monedas',
                'valor' => 2.00,
                'texto' => 'dos',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo' => 'Monedas',
                'valor' => 1.00,
                'texto' => 'uno',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tes_discriminaciones_monetarias');
    }
};
