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
                'nombre' => 'Billetes',
                'activo' => true,
            ],
            [
                'nombre' => 'Monedas',
                'activo' => true,
            ],
        ];

        foreach ($tiposMonedas as $tipo) {
            TesTipoMoneda::create($tipo);
        }
    }
}
