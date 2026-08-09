<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tes_lb_medios') && Schema::hasColumn('tes_lb_medios', 'nombre_corto')) {
            return;
        }
        Schema::table('tes_lb_medios', function (Blueprint $table) {
            $table->string('nombre_corto', 100)->after('nombre')->default('');
        });
    }

    public function down(): void
    {
        Schema::table('tes_lb_medios', function (Blueprint $table) {
            $table->dropColumn('nombre_corto');
        });
    }
};
