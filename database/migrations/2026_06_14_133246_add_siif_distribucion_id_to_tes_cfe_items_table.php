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
        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->foreignId('siif_distribucion_id')->nullable()->after('tes_cfe_id')->constrained('siif_distribucions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->dropForeign(['siif_distribucion_id']);
            $table->dropColumn('siif_distribucion_id');
        });
    }
};
