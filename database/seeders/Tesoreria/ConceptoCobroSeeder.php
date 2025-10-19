<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\CajaDiaria\ConceptoCobro;

class ConceptoCobroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conceptos = [
            [
                'id' => 1,
                'nombre' => 'ARRENDAMIENTO JPM',
                'descripcion' => 'Arrendamiento de vivienda de la Jefatura de Policía de Montevideo',
                'activo' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:14:27',
                'updated_at' => '2025-10-18 03:14:27'
            ]
        ];

        foreach ($conceptos as $concepto) {
            ConceptoCobro::create($concepto);
        }
    }
}
