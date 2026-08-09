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
        Schema::table('tes_servicios', function (Blueprint $table) {
            $table->renameColumn('valor_ur', 'valor_ui');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_servicios', function (Blueprint $table) {
            $table->renameColumn('valor_ui', 'valor_ur');
        });
    }
};
