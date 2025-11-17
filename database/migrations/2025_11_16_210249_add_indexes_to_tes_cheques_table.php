<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tes_cheques', function (Blueprint $table) {
            $table->index('emitido_por');
            $table->index('anulado_por');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
            $table->index('planilla_anulada_por');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cheques', function (Blueprint $table) {
            $table->dropIndex(['emitido_por']);
            $table->dropIndex(['anulado_por']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['deleted_by']);
            $table->dropIndex(['planilla_anulada_por']);
        });
    }
};