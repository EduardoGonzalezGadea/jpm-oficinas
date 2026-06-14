<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a column only if it doesn't exist.
     */
    private function addColumnIfMissing(string $table, string $column, string $type, array $params = []): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column, $type, $params) {
            if ($type === 'softDeletes') {
                $table->softDeletes();
            } elseif ($type === 'unsignedInteger') {
                $col = $table->unsignedInteger($column)->nullable();
                if (isset($params['after'])) {
                    $col->after($params['after']);
                }
            }
        });
    }

    /**
     * Add foreign key if column exists and FK doesn't already exist.
     */
    private function addForeignKeyIfMissing(string $table, string $column): void
    {
        if (!Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->foreign($column)->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // FK likely already exists
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================================
        // TABLAS CON softDeletes PERO SIN created_by/updated_by/deleted_by
        // ============================================================

        $this->addColumnIfMissing('tes_caja_chica', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_caja_chica', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_caja_chica', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_cch_dependencias', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_cch_dependencias', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_cch_dependencias', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_cch_acreedores', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_cch_acreedores', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_cch_acreedores', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_cch_pendientes', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_cch_pendientes', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_cch_pendientes', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_cch_movimientos', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_cch_movimientos', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_cch_movimientos', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_cch_pagos', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_cch_pagos', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_cch_pagos', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_arrendamientos', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_arrendamientos', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_arrendamientos', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_arr_planillas', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_arr_planillas', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_arr_planillas', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_eventuales_planillas', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_eventuales_planillas', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_eventuales_planillas', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_eventuales', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_eventuales', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_eventuales', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_eventuales_instituciones', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_eventuales_instituciones', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_eventuales_instituciones', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_multas', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_multas', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_multas', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        $this->addColumnIfMissing('tes_cfe_pendientes', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_cfe_pendientes', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_cfe_pendientes', 'deleted_by', 'unsignedInteger', ['after' => 'deleted_at']);

        // ============================================================
        // TABLAS CON created_by/updated_by PERO SIN deleted_by
        // ============================================================

        $this->addColumnIfMissing('tes_porte_armas_planillas', 'deleted_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_tenencia_armas_planillas', 'deleted_by', 'unsignedInteger', ['after' => 'updated_at']);

        // ============================================================
        // TABLAS SIN softDeletes NI auditoría
        // ============================================================

        $this->addColumnIfMissing('tes_deposito_vehiculo_planillas', 'deleted_at', 'softDeletes');
        $this->addColumnIfMissing('tes_deposito_vehiculo_planillas', 'deleted_by', 'unsignedInteger', ['after' => 'updated_at']);

        $this->addColumnIfMissing('tes_anulaciones', 'deleted_at', 'softDeletes');
        $this->addColumnIfMissing('tes_anulaciones', 'created_by', 'unsignedInteger', ['after' => 'created_at']);
        $this->addColumnIfMissing('tes_anulaciones', 'updated_by', 'unsignedInteger', ['after' => 'updated_at']);
        $this->addColumnIfMissing('tes_anulaciones', 'deleted_by', 'unsignedInteger');

        // ============================================================
        // FOREIGN KEYS
        // ============================================================

        $tables = [
            'tes_caja_chica', 'tes_cch_dependencias', 'tes_cch_acreedores',
            'tes_cch_pendientes', 'tes_cch_movimientos', 'tes_cch_pagos',
            'tes_arrendamientos', 'tes_arr_planillas',
            'tes_eventuales_planillas', 'tes_eventuales', 'tes_eventuales_instituciones',
            'tes_multas', 'tes_cfe_pendientes',
            'tes_porte_armas_planillas', 'tes_tenencia_armas_planillas',
            'tes_deposito_vehiculo_planillas', 'tes_anulaciones',
        ];

        foreach ($tables as $table) {
            $this->addForeignKeyIfMissing($table, 'created_by');
            $this->addForeignKeyIfMissing($table, 'updated_by');
            $this->addForeignKeyIfMissing($table, 'deleted_by');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'tes_anulaciones', 'tes_deposito_vehiculo_planillas',
            'tes_tenencia_armas_planillas', 'tes_porte_armas_planillas',
            'tes_cfe_pendientes', 'tes_multas',
            'tes_eventuales_instituciones', 'tes_eventuales', 'tes_eventuales_planillas',
            'tes_arr_planillas', 'tes_arrendamientos',
            'tes_cch_pagos', 'tes_cch_movimientos', 'tes_cch_pendientes',
            'tes_cch_acreedores', 'tes_cch_dependencias', 'tes_caja_chica',
        ];

        foreach ($tables as $tableName) {
            try {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = Schema::getColumnListing($tableName);

                    foreach (['created_by', 'updated_by', 'deleted_by'] as $col) {
                        if (in_array($col, $columns)) {
                            try { $table->dropForeign([$col]); } catch (\Exception $e) {}
                        }
                    }

                    $toDrop = [];
                    foreach (['created_by', 'updated_by', 'deleted_at', 'deleted_by'] as $col) {
                        if (in_array($col, $columns)) {
                            $toDrop[] = $col;
                        }
                    }
                    if (!empty($toDrop)) {
                        $table->dropColumn($toDrop);
                    }
                });
            } catch (\Exception $e) {
                // Ignore errors during rollback
            }
        }
    }
};