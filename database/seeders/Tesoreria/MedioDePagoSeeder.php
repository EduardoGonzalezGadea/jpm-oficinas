<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tesoreria\MedioDePago;

class MedioDePagoSeeder extends Seeder
{
    public function run()
    {
        $mediosDePago = [
            ['nombre' => 'Efectivo', 'contado' => true],
            ['nombre' => 'Transferencia'],
            ['nombre' => 'POS'],
            ['nombre' => 'Cheque'],
        ];

        foreach ($mediosDePago as $medio) {
            MedioDePago::create($medio);
        }
    }
}
