<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TesMultasItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Tesoreria\TesMultasItems::updateOrCreate(
            ['detalle' => 'EXCESO DE VELOCIDAD'],
            ['descripcion' => 'Infracción por exceder los límites de velocidad permitidos.', 'importe' => 1500.00]
        );

        \App\Models\Tesoreria\TesMultasItems::updateOrCreate(
            ['detalle' => 'SIN CINTURÓN DE SEGURIDAD'],
            ['descripcion' => 'Conducir o viajar sin utilizar el cinturón de seguridad.', 'importe' => 800.00]
        );

        \App\Models\Tesoreria\TesMultasItems::updateOrCreate(
            ['detalle' => 'ESTACIONAMIENTO PROHIBIDO'],
            ['descripcion' => 'Estacionar en zona no permitida o reservada.', 'importe' => 1200.00]
        );
    }
}
