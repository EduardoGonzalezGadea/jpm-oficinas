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
        Schema::table('tes_arrendamientos', function (Blueprint $table) {
            $table->foreignId('planilla_id')->nullable()->constrained('tes_arr_planillas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_arrendamientos', function (Blueprint $table) {
            $table->dropForeign(['planilla_id']);
            $table->dropColumn('planilla_id');
        });
    }
};
