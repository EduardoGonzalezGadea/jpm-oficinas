<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_multas_items', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->index('documento_serie');
            $table->index('emisor_ruc');
            $table->index('vencimiento');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('activo');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('tes_multas_items', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropIndex(['documento_serie']);
            $table->dropIndex(['emisor_ruc']);
            $table->dropIndex(['vencimiento']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['activo']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
