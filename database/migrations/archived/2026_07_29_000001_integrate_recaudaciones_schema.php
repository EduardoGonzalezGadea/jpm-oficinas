<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->unificarMediosDePago();
        $this->agregarIndicesLibroDiario();
        $this->agregarTotalRecaudadoAPlanillas();
    }

    public function down(): void
    {
        if (Schema::hasTable('tes_planilla_ers') && Schema::hasColumn('tes_planilla_ers', 'total_recaudado')) {
            Schema::table('tes_planilla_ers', function (Blueprint $table) {
                $table->dropColumn('total_recaudado');
            });
        }

        if (Schema::hasTable('tes_libro_diario')) {
            Schema::table('tes_libro_diario', function (Blueprint $table) {
                $table->dropIndex('idx_libro_diario_cch_origen');
                $table->dropIndex('idx_libro_diario_fecha_tipo_deleted');
                $table->dropIndex('idx_libro_diario_cfe_contra');
            });
        }

        $this->restaurarTablaLbMedios();
    }

    private function unificarMediosDePago(): void
    {
        if (Schema::hasTable('tes_lb_medios')) {
            DB::transaction(function () {
                $lbMedios = DB::table('tes_lb_medios')->get();
                $migrados = 0;

                foreach ($lbMedios as $lb) {
                    $existe = DB::table('tes_medio_de_pagos')
                        ->where('nombre', $lb->nombre)
                        ->exists();

                    if (!$existe) {
                        DB::table('tes_medio_de_pagos')->insert([
                            'nombre' => $lb->nombre,
                            'nombre_corto' => $lb->nombre_corto ?: $lb->nombre,
                            'descripcion' => 'Migrado desde tes_lb_medios',
                            'activo' => true,
                            'contado' => false,
                            'es_libro_diario' => true,
                            'es_recaudacion' => false,
                            'orden' => 99,
                            'created_at' => $lb->created_at ?? now(),
                            'updated_at' => $lb->updated_at ?? now(),
                        ]);
                        $migrados++;
                    }
                }

                if ($migrados > 0) {
                    Log::info("IntegracionSchema: {$migrados} registros migrados de tes_lb_medios a tes_medio_de_pagos");
                }
            });

            Schema::dropIfExists('tes_lb_medios');
        }
    }

    private function restaurarTablaLbMedios(): void
    {
        if (!Schema::hasTable('tes_lb_medios')) {
            Schema::create('tes_lb_medios', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('nombre_corto', 100)->default('');
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->unsignedInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function agregarIndicesLibroDiario(): void
    {
        if (!Schema::hasTable('tes_libro_diario')) {
            return;
        }

        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $existing = array_keys($sm->listTableIndexes('tes_libro_diario'));

        Schema::table('tes_libro_diario', function (Blueprint $table) use ($existing) {
            if (!in_array('idx_libro_diario_cch_origen', $existing)) {
                $table->index(['cch_origen_type', 'cch_origen_id'], 'idx_libro_diario_cch_origen');
            }

            if (!in_array('idx_libro_diario_fecha_tipo_deleted', $existing)) {
                $table->index(['fecha', 'tipo_id', 'deleted_at'], 'idx_libro_diario_fecha_tipo_deleted');
            }

            if (!in_array('idx_libro_diario_cfe_contra', $existing)) {
                $table->index(['cfe_id', 'es_contra_asiento'], 'idx_libro_diario_cfe_contra');
            }
        });
    }

    private function agregarTotalRecaudadoAPlanillas(): void
    {
        if (!Schema::hasTable('tes_planilla_ers')) {
            return;
        }

        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            if (!Schema::hasColumn('tes_planilla_ers', 'total_recaudado')) {
                $table->decimal('total_recaudado', 12, 2)->default(0)
                    ->after('ingresos_numero');
            }
        });

        DB::statement("
            UPDATE tes_planilla_ers pe
            SET pe.total_recaudado = (
                SELECT COALESCE(SUM(ci.importe), 0)
                FROM tes_cfe_items ci
                WHERE ci.planilla_er_id = pe.id
                  AND ci.deleted_at IS NULL
            )
        ");
    }
};
