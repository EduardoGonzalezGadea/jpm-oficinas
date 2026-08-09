<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->foreignId('planilla_er_id')
                ->nullable()
                ->after('total_a_pagar')
                ->constrained('tes_planilla_ers')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropForeign(['planilla_er_id']);
            $table->dropColumn('planilla_er_id');
        });
    }
};
