<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tes_libro_diario')) {
            return;
        }

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->string('cch_origen_type', 50)->nullable()->after('es_contra_asiento');
            $table->unsignedBigInteger('cch_origen_id')->nullable()->after('cch_origen_type');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tes_libro_diario')) {
            return;
        }

        Schema::table('tes_libro_diario', function (Blueprint $table) {
            $table->dropColumn(['cch_origen_type', 'cch_origen_id']);
        });
    }
};
