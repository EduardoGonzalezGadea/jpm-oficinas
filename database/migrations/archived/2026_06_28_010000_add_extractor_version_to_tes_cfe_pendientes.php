<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('tes_cfe_pendientes', 'extractor_version')) {
            Schema::table('tes_cfe_pendientes', function (Blueprint $table) {
                $table->string('extractor_version', 50)->nullable()->after('pdf_hash');
            });
        }

        if (!Schema::hasColumn('tes_cfe_pendientes', 'datos_modificados')) {
            Schema::table('tes_cfe_pendientes', function (Blueprint $table) {
                $table->json('datos_modificados')->nullable()->after('datos_extraidos');
            });
        }
    }

    public function down()
    {
        Schema::table('tes_cfe_pendientes', function (Blueprint $table) {
            $table->dropColumn(['extractor_version', 'datos_modificados']);
        });
    }
};
