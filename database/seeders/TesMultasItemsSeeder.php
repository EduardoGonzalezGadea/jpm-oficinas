<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TesMultasItemsSeeder extends Seeder
{
    public function run()
    {
        \App\Models\Tesoreria\TesMultasItems::updateOrCreate(
            ['codigo' => 'EXCESO_VELOCIDAD'],
            ['descripcion' => 'Infracción por exceder los límites de velocidad permitidos.', 'subtotal' => 1500.00]
        );

        \App\Models\Tesoreria\TesMultasItems::updateOrCreate(
            ['codigo' => 'SIN_CINTURON'],
            ['descripcion' => 'Conducir o viajar sin utilizar el cinturón de seguridad.', 'subtotal' => 800.00]
        );

        \App\Models\Tesoreria\TesMultasItems::updateOrCreate(
            ['codigo' => 'ESTACIONAMIENTO'],
            ['descripcion' => 'Estacionar en zona no permitida o reservada.', 'subtotal' => 1200.00]
        );
    }
}
