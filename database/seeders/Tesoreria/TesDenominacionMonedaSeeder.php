<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tesoreria\TesDenominacionMoneda;

class TesDenominacionMonedaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $denominaciones = [
            // Billetes Uruguayos
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 20,
                'valor' => 20.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 20 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 50,
                'valor' => 50.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 50 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 100,
                'valor' => 100.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 100 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 200,
                'valor' => 200.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 200 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 500,
                'valor' => 500.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 500 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 1000,
                'valor' => 1000.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 1000 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Billetes',
                'denominacion' => 2000,
                'valor' => 2000.00,
                'moneda' => 'UYU',
                'descripcion' => 'Billete de 2000 pesos uruguayos',
                'activo' => true
            ],

            // Monedas Uruguayas
            [
                'tipo_moneda' => 'Monedas',
                'denominacion' => 1,
                'valor' => 1.00,
                'moneda' => 'UYU',
                'descripcion' => 'Moneda de 1 peso uruguayo',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Monedas',
                'denominacion' => 2,
                'valor' => 2.00,
                'moneda' => 'UYU',
                'descripcion' => 'Moneda de 2 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Monedas',
                'denominacion' => 5,
                'valor' => 5.00,
                'moneda' => 'UYU',
                'descripcion' => 'Moneda de 5 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Monedas',
                'denominacion' => 10,
                'valor' => 10.00,
                'moneda' => 'UYU',
                'descripcion' => 'Moneda de 10 pesos uruguayos',
                'activo' => true
            ],
            [
                'tipo_moneda' => 'Monedas',
                'denominacion' => 50,
                'valor' => 50.00,
                'moneda' => 'UYU',
                'descripcion' => 'Moneda de 50 pesos uruguayos',
                'activo' => true
            ]
        ];

        foreach ($denominaciones as $denominacion) {
            TesDenominacionMoneda::create($denominacion);
        }
    }
}
