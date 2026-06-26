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
                'clave' => 'tesoreria',
                'descripcion' => 'Módulo de gestión de tesorería y finanzas',
                'activo' => true,
            ],
            [
                'nombre' => 'Asesoría Contable',
                'clave' => 'asesoria_contable',
                'descripcion' => 'Módulo de información para Asesoría Contable',
                'activo' => true,
            ],
        ];

        foreach ($modulos as $modulo) {
            Modulo::create($modulo);
        }
    }
}
