<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_certificados_residencia', function (Blueprint $table) {
            if (!Schema::hasColumn('tes_certificados_residencia', 'monto')) {
                $table->decimal('monto', 12, 2)->nullable()->after('numero_recibo');
            }
        });

        // Actualizar registros existentes que ya fueron entregados con $55
        DB::table('tes_certificados_residencia')
            ->whereNotNull('fecha_entregado')
            ->whereNull('deleted_at')
            ->update(['monto' => 55.00]);
    }

    public function down(): void
    {
        Schema::table('tes_certificados_residencia', function (Blueprint $table) {
            $table->dropColumn('monto');
        });
    }
};
