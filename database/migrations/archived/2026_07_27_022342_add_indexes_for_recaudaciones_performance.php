<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega índices optimizados para mejorar performance de queries
     * en el módulo de Gestión de Recaudaciones.
     * 
     * Mejora 1.3 - Plan de Mejoras Gestión de Recaudaciones
     */
    public function up(): void
    {
        // Índices para tes_cfes
        if (!Schema::hasTable('tes_cfes')) {
            return;
        }

        Schema::table('tes_cfes', function (Blueprint $table) {
            // Índice compuesto para filtros de fecha y dependencia
            // Usado en: Recaudaciones/Index, EstadosRecaudacion queries
            $table->index(['fecha', 'siif_distribucion_dependencia_id'], 'idx_tes_cfes_fecha_dependencia');
            
            // Índice para soft deletes con fecha
            // Optimiza whereNull('deleted_at') + fecha
            $table->index(['deleted_at', 'fecha'], 'idx_tes_cfes_deleted_fecha');
        });

        // Índices para tes_cfe_items
        if (!Schema::hasTable('tes_cfe_items')) {
            return;
        }

        Schema::table('tes_cfe_items', function (Blueprint $table) {
            // Índice compuesto para planilla + distribución + confirmado
            // Usado en: EstadosRecaudacion/Confirmar, queries de confirmación
            $table->index(['planilla_er_id', 'siif_distribucion_id', 'confirmado'], 'idx_tes_cfe_items_planilla_dist_conf');
            
            // Índice para ítems sin asignar (whereNull planilla_er_id)
            // Usado en: cargarGrupos() - búsqueda de ítems disponibles
            $table->index(['planilla_er_id', 'deleted_at'], 'idx_tes_cfe_items_planilla_deleted');
            
            // Índice para relación con CFE
            // Optimiza joins frecuentes
            $table->index(['tes_cfe_id', 'deleted_at'], 'idx_tes_cfe_items_cfe_deleted');
        });

        // Índices para tes_planilla_ers
        if (!Schema::hasTable('tes_planilla_ers')) {
            return;
        }

        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            // Índice compuesto para filtros comunes
            // Usado en: listados y búsquedas de planillas
            $table->index(['fecha', 'tipo_id', 'dependencia_id', 'confirmada'], 'idx_tes_planilla_ers_fecha_tipo_dep_conf');
            
            // Índice para búsqueda por turno y fecha
            // Usado en: búsqueda de planillas nocturnas/diurnas
            $table->index(['fecha', 'turno', 'deleted_at'], 'idx_tes_planilla_ers_fecha_turno_deleted');
            
            // Índice para soft deletes con ordering
            // Optimiza listados ordenados
            $table->index(['deleted_at', 'fecha', 'id'], 'idx_tes_planilla_ers_deleted_fecha_id');
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina los índices agregados de forma segura.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tes_cfes')) {
            return;
        }

        Schema::table('tes_cfes', function (Blueprint $table) {
            $table->dropIndex('idx_tes_cfes_fecha_dependencia');
            $table->dropIndex('idx_tes_cfes_deleted_fecha');
        });

        Schema::table('tes_cfe_items', function (Blueprint $table) {
            $table->dropIndex('idx_tes_cfe_items_planilla_dist_conf');
            $table->dropIndex('idx_tes_cfe_items_planilla_deleted');
            $table->dropIndex('idx_tes_cfe_items_cfe_deleted');
        });

        Schema::table('tes_planilla_ers', function (Blueprint $table) {
            $table->dropIndex('idx_tes_planilla_ers_fecha_tipo_dep_conf');
            $table->dropIndex('idx_tes_planilla_ers_fecha_turno_deleted');
            $table->dropIndex('idx_tes_planilla_ers_deleted_fecha_id');
        });
    }
};
