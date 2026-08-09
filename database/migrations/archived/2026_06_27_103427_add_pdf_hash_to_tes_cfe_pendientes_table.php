<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_cfe_pendientes', function (Blueprint $table) {
            $table->char('pdf_hash', 64)->nullable()->after('source_url');
            $table->unique('pdf_hash', 'tes_cfe_pendientes_pdf_hash_unique');
        });
    }

    public function down()
    {
        Schema::table('tes_cfe_pendientes', function (Blueprint $table) {
            $table->dropUnique('tes_cfe_pendientes_pdf_hash_unique');
            $table->dropColumn('pdf_hash');
        });
    }
};