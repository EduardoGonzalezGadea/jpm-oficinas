<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\CajaDiaria\ConceptoCobroCampo;

class ConceptoCobroCampoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $campos = [
            [
                'id' => 1,
                'concepto_id' => 1,
                'nombre' => 'Ingreso',
                'titulo' => 'Ingreso',
                'tipo' => 'number',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:17:39',
                'updated_at' => '2025-10-18 03:17:39'
            ],
            [
                'id' => 2,
                'concepto_id' => 1,
                'nombre' => 'nombre',
                'titulo' => 'Nombre',
                'tipo' => 'text',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:18:34',
                'updated_at' => '2025-10-18 03:20:16'
            ],
            [
                'id' => 3,
                'concepto_id' => 1,
                'nombre' => 'cedula',
                'titulo' => 'Cédula',
                'tipo' => 'text',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:19:57',
                'updated_at' => '2025-10-18 03:19:57'
            ],
            [
                'id' => 4,
                'concepto_id' => 1,
                'nombre' => 'telefono',
                'titulo' => 'Teléfono',
                'tipo' => 'text',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:20:40',
                'updated_at' => '2025-10-18 03:20:40'
            ],
            [
                'id' => 5,
                'concepto_id' => 1,
                'nombre' => 'direccion',
                'titulo' => 'Dirección',
                'tipo' => 'text',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:21:20',
                'updated_at' => '2025-10-18 03:21:20'
            ],
            [
                'id' => 6,
                'concepto_id' => 1,
                'nombre' => 'orden_cobro',
                'titulo' => 'Orden de Cobro',
                'tipo' => 'text',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:22:12',
                'updated_at' => '2025-10-18 03:22:12'
            ],
            [
                'id' => 7,
                'concepto_id' => 1,
                'nombre' => 'confirmado',
                'titulo' => 'Confirmado',
                'tipo' => 'checkbox',
                'requerido' => false,
                'opciones' => null,
                'orden' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2025-10-18 03:23:16',
                'updated_at' => '2025-10-18 03:23:16'
            ]
        ];

        foreach ($campos as $campo) {
            ConceptoCobroCampo::create($campo);
        }
    }
}
