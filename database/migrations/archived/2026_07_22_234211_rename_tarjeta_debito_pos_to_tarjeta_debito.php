<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tes_medio_de_pagos')
            ->where('nombre', 'Tarjeta de Débito (POS)')
            ->update(['nombre' => 'Tarjeta de Débito']);

        Log::info('Migracion: renombrado medio de pago "Tarjeta de Débito (POS)" a "Tarjeta de Débito"');
    }

    public function down(): void
    {
        DB::table('tes_medio_de_pagos')
            ->where('nombre', 'Tarjeta de Débito')
            ->update(['nombre' => 'Tarjeta de Débito (POS)']);

        Log::info('Migracion: revertido nombre de "Tarjeta de Débito" a "Tarjeta de Débito (POS)"');
    }
};
