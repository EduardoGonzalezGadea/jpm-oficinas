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
        Schema::table('tes_arr_planillas', function (Blueprint $table) {
            // Drop the existing unique index on 'numero'
            $table->dropUnique(['numero']);

            // Add a new unique index on 'numero' and 'deleted_at'
            // This allows multiple entries with the same 'numero' if they are soft-deleted
            $table->unique(['numero', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_arr_planillas', function (Blueprint $table) {
            // Drop the composite unique index
            $table->dropUnique(['numero', 'deleted_at']);

            // Re-add the original unique index on 'numero'
            $table->unique('numero');
        });
    }
};
