<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tesoreria\TesTipoMoneda;

class TesTipoMonedaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tiposMonedas = [
            [
                'codigo' => 'BILLETES',
                'nombre' => 'Billetes',
                'simbolo' => null,
                'activo' => true,
            ],
            [
                'codigo' => 'MONEDAS',
                'nombre' => 'Monedas',
                'simbolo' => null,
                'activo' => true,
            ],
        ];

        foreach ($tiposMonedas as $tipo) {
            TesTipoMoneda::create($tipo);
        }
    }
}
