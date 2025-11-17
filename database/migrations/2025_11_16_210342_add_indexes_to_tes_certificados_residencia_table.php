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
        Schema::table('tes_certificados_residencia', function (Blueprint $table) {
            $table->index('receptor_id');
            $table->index('entregador_id');
            $table->index('devolucion_user_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_certificados_residencia', function (Blueprint $table) {
            $table->dropIndex(['receptor_id']);
            $table->dropIndex(['entregador_id']);
            $table->dropIndex(['devolucion_user_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
            $table->dropIndex(['deleted_by']);
        });
    }
};