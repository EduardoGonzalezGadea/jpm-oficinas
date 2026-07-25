<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $catalogoFinal = [
            [
                'nombre' => 'Efectivo',
                'nombre_corto' => 'Efectivo',
                'contado' => 1,
                'orden' => 1,
                'descripcion' => 'Dinero físico en billetes/monedas',
            ],
            [
                'nombre' => 'Cheque',
                'nombre_corto' => 'Cheque',
                'contado' => 0,
                'orden' => 2,
                'descripcion' => 'Cheque bancario (propios o de terceros)',
            ],
            [
                'nombre' => 'Transferencia Bancaria',
                'nombre_corto' => 'Transferencia',
                'contado' => 0,
                'orden' => 3,
                'descripcion' => 'Transferencia entre cuentas (BROU, otra)',
            ],
            [
                'nombre' => 'Tarjeta de Débito (POS)',
                'nombre_corto' => 'Débito (POS)',
                'contado' => 0,
                'orden' => 4,
                'descripcion' => 'Tarjeta de débito terminal POS',
            ],
        ];

        $mapeoVariantes = [
            'efectivo' => 'Efectivo',
            'contado' => 'Efectivo',
            'cash' => 'Efectivo',
            'cheque' => 'Cheque',
            'transferencia' => 'Transferencia Bancaria',
            'transferencia bancaria' => 'Transferencia Bancaria',
            'brou' => 'Transferencia Bancaria',
            'deposito' => 'Transferencia Bancaria',
            'depósito' => 'Transferencia Bancaria',
            'siif' => 'Transferencia Bancaria',
            'pos' => 'Tarjeta de Débito (POS)',
            'debito' => 'Tarjeta de Débito (POS)',
            'débito' => 'Tarjeta de Débito (POS)',
            'tarjeta' => 'Tarjeta de Débito (POS)',
            'tarjeta de débito' => 'Tarjeta de Débito (POS)',
            'credito' => 'Tarjeta de Débito (POS)',
            'crédito' => 'Tarjeta de Débito (POS)',
        ];

        $helperNormalizar = function (string $texto) use ($mapeoVariantes): ?string {
            $texto = mb_strtolower(trim($texto));
            if ($texto === '') return null;

            if (isset($mapeoVariantes[$texto])) {
                return $mapeoVariantes[$texto];
            }

            foreach ($mapeoVariantes as $variant => $canon) {
                if (str_contains($texto, $variant)) {
                    return $canon;
                }
            }
            return null;
        };

        DB::transaction(function () use ($catalogoFinal, $helperNormalizar) {
            foreach ($catalogoFinal as $cat) {
                DB::table('tes_medio_de_pagos')->updateOrInsert(
                    ['nombre' => $cat['nombre']],
                    [
                        'nombre_corto' => $cat['nombre_corto'],
                        'contado' => $cat['contado'],
                        'orden' => $cat['orden'],
                        'descripcion' => $cat['descripcion'],
                        'es_libro_diario' => true,
                        'es_recaudacion' => true,
                        'activo' => true,
                    ]
                );
            }

            $registrosHistoricos = DB::table('tes_lb_medios')->get();
            foreach ($registrosHistoricos as $lb) {
                $canonico = $helperNormalizar($lb->nombre);
                if ($canonico === null) {
                    Log::warning("MigracionMedios: LbMedio sin mapeo id={$lb->id} nombre={$lb->nombre}");
                    continue;
                }

                $canonicoRow = DB::table('tes_medio_de_pagos')->where('nombre', $canonico)->first();
                if ($canonicoRow && empty($canonicoRow->nombre_corto) && !empty($lb->nombre_corto)) {
                    DB::table('tes_medio_de_pagos')
                        ->where('id', $canonicoRow->id)
                        ->update(['nombre_corto' => $lb->nombre_corto]);
                }
            }

            $nombresCanonicos = array_column($catalogoFinal, 'nombre');
            $desactivados = DB::table('tes_medio_de_pagos')
                ->whereNotIn('nombre', $nombresCanonicos)
                ->where('activo', true)
                ->update(['activo' => false, 'orden' => 99]);

            if ($desactivados > 0) {
                Log::info("MigracionMedios: se desactivaron {$desactivados} registros no canonicos de tes_medio_de_pagos (pendientes de limpieza manual)");
            }

            $total = DB::table('tes_medio_de_pagos')->count();
            $activos = DB::table('tes_medio_de_pagos')->where('activo', true)->count();
            Log::info("MigracionMedios: {$activos} activos, {$total} totales (canonicos: 4 activos)");
        });
    }

    public function down(): void
    {
        DB::table('tes_medio_de_pagos')
            ->whereIn('nombre', [
                'Efectivo', 'Cheque',
                'Transferencia Bancaria', 'Tarjeta de Débito (POS)',
            ])->update([
                'nombre_corto' => '',
                'contado' => 0,
                'es_libro_diario' => true,
                'es_recaudacion' => true,
                'orden' => 0,
            ]);
    }
};
