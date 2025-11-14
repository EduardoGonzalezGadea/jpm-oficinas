<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Modulo;

class ModuloSeeder extends Seeder
{
    public function run()
    {
        $modulos = [
            [
                'nombre' => 'Tesorería',
                'descripcion' => 'Módulo de gestión de tesorería y finanzas',
                'activo' => true,
            ],
        ];

        foreach ($modulos as $modulo) {
            Modulo::create($modulo);
        }
    }
}
