<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->fixTesMultasCobradas();
        $this->fixTesMultasItems();
        $this->fixTesMultas3032023();
    }

    private function fixTesMultasCobradas(): void
    {
        if (! Schema::hasTable('tes_multas_cobradas')) {
            return;
        }

        $this->dropForeignIfExists('tes_multas_cobradas', 'tes_multas_cobradas_planilla_id_foreign');
        $this->dropForeignIfExists('tes_multas_cobradas', 'tes_multas_cobradas_medio_pago_id_foreign');

        Schema::table('tes_multas_cobradas', function (Blueprint $table) {
            // Eliminar índices antes de las columnas
            try { $table->dropIndex('tes_multas_cobradas_recibo_serie_recibo_numero_unique'); } catch (\Exception) {}
            try { $table->dropIndex('tes_multas_cobradas_recibo_fecha_index'); } catch (\Exception) {}
            try { $table->dropIndex('tes_multas_cobradas_planilla_id_index'); } catch (\Exception) {}

            // Eliminar columnas del schema anterior
            foreach (['telefono', 'recibo_serie', 'recibo_numero', 'recibo_fecha',
                     'orden_cobro', 'medio_pago_id', 'medio_pago_nombre', 'concepto',
                     'planilla_id'] as $col) {
                if (Schema::hasColumn('tes_multas_cobradas', $col)) {
                    $table->dropColumn($col);
                }
            }

            // Agregar columnas nuevas
            if (! Schema::hasColumn('tes_multas_cobradas', 'recibo')) {
                $table->string('recibo')->after('id');
            }
            if (! Schema::hasColumn('tes_multas_cobradas', 'adicional')) {
                $table->string('adicional')->nullable()->after('domicilio');
            }
            if (! Schema::hasColumn('tes_multas_cobradas', 'referencias')) {
                $table->text('referencias')->nullable()->after('forma_pago');
            }
            if (! Schema::hasColumn('tes_multas_cobradas', 'adenda')) {
                $table->text('adenda')->nullable()->after('referencias');
            }
        });
    }

    private function fixTesMultasItems(): void
    {
        if (! Schema::hasTable('tes_multas_items')) {
            return;
        }

        $this->dropForeignIfExists('tes_multas_items', 'tes_multas_items_tes_multas_cobradas_id_foreign');

        Schema::table('tes_multas_items', function (Blueprint $table) {
            foreach (['codigo', 'monto_ur', 'monto_pesos', 'subtotal'] as $col) {
                if (Schema::hasColumn('tes_multas_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('tes_multas_items', function (Blueprint $table) {
            if (! $this->foreignExists('tes_multas_items', 'tes_multas_items_tes_multas_cobradas_id_foreign')) {
                $table->foreign('tes_multas_cobradas_id')
                      ->references('id')->on('tes_multas_cobradas')
                      ->onDelete('cascade');
            }
        });
    }

    private function fixTesMultas3032023(): void
    {
        if (Schema::hasTable('tes_multas_303_2023') &&
            Schema::hasColumn('tes_multas_303_2023', 'monto_ur')) {
            Schema::table('tes_multas_303_2023', function (Blueprint $table) {
                $table->dropColumn('monto_ur');
            });
        }
    }

    public function down(): void
    {
        // No se revierte — es una corrección de esquema irreversible
    }

    private function dropForeignIfExists(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropForeign($name);
            });
        } catch (\Exception) {}
    }

    private function foreignExists(string $table, string $name): bool
    {
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            foreach ($sm->listTableForeignKeys($table) as $fk) {
                if ($fk->getName() === $name) {
                    return true;
                }
            }
        } catch (\Exception) {}
        return false;
    }
};
