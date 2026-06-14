<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\SiifDistribucionDependencia;

class SiifDistribucionDependenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dependencias = [
            [
                'id' => 1,
                'dependencia' => 'Jefatura de Policía de Montevideo',
                'abreviatura' => 'JPM'
            ],
            [
                'id' => 2,
                'dependencia' => 'Instituto Nacional de Rehabilitación',
                'abreviatura' => 'INR'
            ],
            [
                'id' => 3,
                'dependencia' => 'Dirección General de Información e Inteligencia',
                'abreviatura' => 'DGII'
            ],
            [
                'id' => 4,
                'dependencia' => 'Dirección Nacional de Policía Científica',
                'abreviatura' => 'DNPCi'
            ]
        ];

        foreach ($dependencias as $dep) {
            SiifDistribucionDependencia::updateOrCreate(['id' => $dep['id']], $dep);
        }
    }
}
