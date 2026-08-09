<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->boolean('confirmado')->default(false)->after('importe');
        });
    }

    public function down()
    {
        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->dropColumn('confirmado');
        });
    }
};
