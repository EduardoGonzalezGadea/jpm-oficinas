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
        Schema::table('tes_multas_cobradas', function (Blueprint $table) {
            $table->dropForeign(['multas_items_id']);
            $table->dropColumn('multas_items_id');
        });

        Schema::table('tes_multas_items', function (Blueprint $table) {
            $table->unsignedBigInteger('tes_multas_cobradas_id')->nullable()->after('id');
            $table->foreign('tes_multas_cobradas_id')->references('id')->on('tes_multas_cobradas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('tes_multas_items', function (Blueprint $table) {
            $table->dropForeign(['tes_multas_cobradas_id']);
            $table->dropColumn('tes_multas_cobradas_id');
        });

        Schema::table('tes_multas_cobradas', function (Blueprint $table) {
            $table->unsignedBigInteger('multas_items_id')->nullable();
            $table->foreign('multas_items_id')->references('id')->on('tes_multas_items');
        });
    }
};
