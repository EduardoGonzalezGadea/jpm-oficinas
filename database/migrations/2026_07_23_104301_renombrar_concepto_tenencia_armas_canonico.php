<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CANON_TENENCIA = 'TÍTULO DE HABILITACIÓN Y TENENCIA DE ARMAS (THATA)';
    private const CANON_TENENCIA_PREVIO = 'TITULO HABILITACIÓN Y TENENCIA DE ARMA (TAHTA)';
    private const CANON_SIIF_TENENCIA = 'Título de Habilitación y Tenencia de Armas (THATA)';

    public function up(): void
    {
        DB::table('tes_caja_conceptos')
            ->where('id', 6)
            ->update(['caja_concepto' => self::CANON_TENENCIA]);

        DB::table('siif_distribucions')
            ->whereIn('id', [121, 122])
            ->update(['concepto' => self::CANON_SIIF_TENENCIA]);
    }

    public function down(): void
    {
        DB::table('tes_caja_conceptos')
            ->where('id', 6)
            ->update(['caja_concepto' => self::CANON_TENENCIA_PREVIO]);

        DB::table('siif_distribucions')
            ->whereIn('id', [121, 122])
            ->update(['concepto' => 'Título Habilitación y Tenencia de Armas (THATA)']);
    }
};
