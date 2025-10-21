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
        // Primero nos aseguramos de que la tabla no exista
        Schema::dropIfExists('tes_cd_pagos');

        Schema::create('tes_cd_pagos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('monto', 10, 2);
            $table->string('medio_pago');
            $table->string('descripcion')->nullable();
            $table->string('numero_comprobante')->nullable();
            $table->foreignId('concepto_id')->nullable()->constrained('tes_cd_conceptos_pago');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'pagos_created_by_foreign')->references('id')->on('users');
            $table->foreign('updated_by', 'pagos_updated_by_foreign')->references('id')->on('users');
            $table->foreign('deleted_by', 'pagos_deleted_by_foreign')->references('id')->on('users');
            $table->foreign('concepto_id', 'pagos_concepto_id_foreign')->references('id')->on('tes_cd_conceptos_pago');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('tes_cd_pagos')) {
            Schema::table('tes_cd_pagos', function (Blueprint $table) {
                // Eliminamos las claves foráneas si existen
                if (Schema::hasColumn('tes_cd_pagos', 'created_by')) {
                    $table->dropForeign('pagos_created_by_foreign');
                }
                if (Schema::hasColumn('tes_cd_pagos', 'updated_by')) {
                    $table->dropForeign('pagos_updated_by_foreign');
                }
                if (Schema::hasColumn('tes_cd_pagos', 'deleted_by')) {
                    $table->dropForeign('pagos_deleted_by_foreign');
                }
                if (Schema::hasColumn('tes_cd_pagos', 'concepto_id')) {
                    $table->dropForeign('pagos_concepto_id_foreign');
                }
            });
        }
        Schema::dropIfExists('tes_cd_pagos');
    }
};
