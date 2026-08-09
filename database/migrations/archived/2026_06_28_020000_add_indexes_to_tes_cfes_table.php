<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->index(['documento_tipo', 'documento_numero'], 'idx_tes_cfes_documento');
            $table->index('fecha', 'idx_tes_cfes_fecha');
            $table->index('receptor_documento_ruc', 'idx_tes_cfes_receptor_ruc');
            $table->index('comprobante_tipo', 'idx_tes_cfes_comprobante_tipo');
            $table->index('deleted_at', 'idx_tes_cfes_deleted_at');
        });

    }

    public function down()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropIndex('idx_tes_cfes_documento');
            $table->dropIndex('idx_tes_cfes_fecha');
            $table->dropIndex('idx_tes_cfes_receptor_ruc');
            $table->dropIndex('idx_tes_cfes_comprobante_tipo');
            $table->dropIndex('idx_tes_cfes_deleted_at');
        });
    }
};
