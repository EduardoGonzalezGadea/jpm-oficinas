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
        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->softDeletes()->after('archivo_pdf_path');
            $table->integer('created_by')->unsigned()->nullable()->after('created_at');
            $table->integer('updated_by')->unsigned()->nullable()->after('updated_at');
            $table->integer('deleted_by')->unsigned()->nullable()->after('deleted_at');
        });

        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->softDeletes()->after('importe');
            $table->integer('created_by')->unsigned()->nullable()->after('created_at');
            $table->integer('updated_by')->unsigned()->nullable()->after('updated_at');
            $table->integer('deleted_by')->unsigned()->nullable()->after('deleted_at');
        });

        Schema::table('tes_cfe_medios_pago', function (Blueprint $table) {
            $table->softDeletes()->after('medio_pago_valor');
            $table->integer('created_by')->unsigned()->nullable()->after('created_at');
            $table->integer('updated_by')->unsigned()->nullable()->after('updated_at');
            $table->integer('deleted_by')->unsigned()->nullable()->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tes_cfe_medios_pago', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'created_by', 'updated_by', 'deleted_by']);
        });

        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'created_by', 'updated_by', 'deleted_by']);
        });

        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'created_by', 'updated_by', 'deleted_by']);
        });
    }
};
