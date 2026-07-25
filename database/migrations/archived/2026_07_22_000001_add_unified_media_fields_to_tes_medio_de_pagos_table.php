<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tes_medio_de_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('tes_medio_de_pagos', 'codigo_soniar')) {
                $table->string('codigo_soniar', 50)->nullable()->after('activo');
            }
            if (!Schema::hasColumn('tes_medio_de_pagos', 'contado')) {
                $table->boolean('contado')->default(false)->after('codigo_soniar');
            }
            if (!Schema::hasColumn('tes_medio_de_pagos', 'nombre_corto')) {
                $table->string('nombre_corto', 100)->default('')->after('nombre');
            }
            if (!Schema::hasColumn('tes_medio_de_pagos', 'es_libro_diario')) {
                $table->boolean('es_libro_diario')->default(true)->after('contado');
            }
            if (!Schema::hasColumn('tes_medio_de_pagos', 'es_recaudacion')) {
                $table->boolean('es_recaudacion')->default(true)->after('es_libro_diario');
            }
            if (!Schema::hasColumn('tes_medio_de_pagos', 'orden')) {
                $table->integer('orden')->default(0)->after('es_recaudacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tes_medio_de_pagos', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_soniar',
                'contado',
                'nombre_corto',
                'es_libro_diario',
                'es_recaudacion',
                'orden',
            ]);
        });
    }
};
