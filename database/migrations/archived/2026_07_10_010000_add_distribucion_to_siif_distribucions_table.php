<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistribucionToSiifDistribucionsTable extends Migration
{
    public function up()
    {
        Schema::table('siif_distribucions', function (Blueprint $table) {
            $table->string('distribucion', 255)->nullable()->default(null)->after('unidad_ejecutora');
        });
    }

    public function down()
    {
        Schema::table('siif_distribucions', function (Blueprint $table) {
            $table->dropColumn('distribucion');
        });
    }
}
