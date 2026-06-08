<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\DistribucionER;
use App\Models\Tesoreria\Concepto;

class DistribucionERSeeder extends Seeder
{
    public function run()
    {
        // Limpiar para evitar duplicados
        DistribucionER::truncate();

        $distribuciones = [
            // Porte de Armas (ID 6) - L.D.
            6 => [
                'default' => [
                    ['fin' => '12', 'inc' => '04', 'ue' => '004', 'porcentaje' => 95, 'codigo' => '04004121520027025'],
                    ['fin' => '12', 'inc' => '04', 'ue' => '001', 'porcentaje' => 5,  'codigo' => '04001121520031846'],
                ]
            ],
            // Tenencia de Armas (THATA) (ID 7) - L.D.
            7 => [
                'default' => [
                    ['fin' => '12', 'inc' => '04', 'ue' => '004', 'porcentaje' => 95, 'codigo' => '04004121520027025'],
                    ['fin' => '12', 'inc' => '04', 'ue' => '001', 'porcentaje' => 5,  'codigo' => '04001121520031846'],
                ]
            ],
            // Certificados de Residencia (ID 3) - L.D.
            3 => [
                'default' => [
                    ['fin' => '12', 'inc' => '04', 'ue' => '004', 'porcentaje' => 95, 'codigo' => '04004121520027025'],
                    ['fin' => '12', 'inc' => '04', 'ue' => '001', 'porcentaje' => 5,  'codigo' => '04001121520031846'],
                ]
            ],
            // Prendas (ID 4) - L.D. (100% 11-05-004)
            4 => [
                'default' => [
                    ['fin' => '11', 'inc' => '05', 'ue' => '004', 'porcentaje' => 100, 'codigo' => '5004111520028920'],
                ]
            ],
            // Depósito de Vehículos (ID 5) - L.D.
            5 => [
                'default' => [
                    ['fin' => '12', 'inc' => '04', 'ue' => '004', 'porcentaje' => 95, 'codigo' => '04004121520027025'],
                    ['fin' => '12', 'inc' => '04', 'ue' => '001', 'porcentaje' => 5,  'codigo' => '04001121520031846'],
                ]
            ],
            // Multas de Tránsito (ID 1)
            1 => [
                'default' => [
                    ['fin' => '12', 'inc' => '04', 'ue' => '004', 'porcentaje' => 61.75, 'codigo' => '04004121520027025'],
                    ['fin' => '11', 'inc' => '05', 'ue' => '004', 'porcentaje' => 10.00, 'codigo' => '05004111520028920'],
                    ['fin' => '12', 'inc' => '04', 'ue' => '001', 'porcentaje' => 3.25,  'codigo' => '04001121520031846'],
                    ['fin' => '12', 'inc' => '04', 'ue' => '004', 'porcentaje' => 25.00, 'codigo' => '04004121110000098'],
                ]
            ],
            // Servicios Art. 222 (ID 9) - DISTRIBUCIÓm SEGÚN TURNO
            9 => [
                'Diurno' => [
                    ['fin' => '12', 'inc' => '4', 'ue' => '1', 'porcentaje' => 0.71, 'codigo' => '04001121520031846'],
                    ['fin' => '12', 'inc' => '4', 'ue' => '4', 'porcentaje' => 90.71, 'codigo' => '04004120000000222'],
                    ['fin' => '12', 'inc' => '4', 'ue' => '4', 'porcentaje' => 8.58,  'codigo' => '04004121520027236'],
                ],
                'Nocturno' => [
                    ['fin' => '12', 'inc' => '4', 'ue' => '1', 'porcentaje' => 0.71, 'codigo' => '04001121520031846'],
                    ['fin' => '12', 'inc' => '4', 'ue' => '4', 'porcentaje' => 82.16, 'codigo' => '04004120000000222'],
                    ['fin' => '12', 'inc' => '4', 'ue' => '4', 'porcentaje' => 17.13, 'codigo' => '04004121520027236'],
                ]
            ],
        ];

        foreach ($distribuciones as $conceptoId => $variants) {
            foreach ($variants as $turno => $rows) {
                foreach ($rows as $row) {
                    DistribucionER::create([
                        'concepto_id'    => $conceptoId,
                        'financiador'    => "Fin {$row['fin']} Inc {$row['inc']} UE {$row['ue']}",
                        'porcentaje'     => $row['porcentaje'],
                        'cuenta_contable' => $row['codigo'],
                        'turno'          => ($turno === 'default' ? null : $turno),
                        'activo'         => true,
                    ]);
                }
            }
        }
    }
}
