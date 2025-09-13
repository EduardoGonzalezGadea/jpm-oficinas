<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tesoreria\MedioDePago;

class MedioDePagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $mediosDePago = [
            [
                'nombre' => 'Efectivo',
                'descripcion' => 'Pago en efectivo',
                'activo' => true
            ],
            [
                'nombre' => 'Transferencia',
                'descripcion' => 'Transferencia bancaria',
                'activo' => true
            ],
            [
                'nombre' => 'POS',
                'descripcion' => 'Pago con tarjeta de débito',
                'activo' => true
            ],
            [
                'nombre' => 'Cheque',
                'descripcion' => 'Pago con cheque',
                'activo' => true
            ]
        ];

        foreach ($mediosDePago as $medio) {
            MedioDePago::create($medio);
        }
    }
}
