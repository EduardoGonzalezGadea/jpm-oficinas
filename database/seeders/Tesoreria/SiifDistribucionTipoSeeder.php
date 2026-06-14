<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\SiifDistribucionTipo;

class SiifDistribucionTipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tipos = [
            [
                'id' => 1,
                'tipo' => 'Recaudación Artículo 222'
            ],
            [
                'id' => 2,
                'tipo' => 'Recaudación Diaria'
            ]
        ];

        foreach ($tipos as $tipo) {
            SiifDistribucionTipo::updateOrCreate(['id' => $tipo['id']], $tipo);
        }
    }
}
