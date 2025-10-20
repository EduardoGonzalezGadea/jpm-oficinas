<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\CajaDiaria\ConceptoPago;
use Illuminate\Support\Facades\DB;

class ConceptoPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tes_cd_conceptos_pago')->delete();

        $conceptos = [
            [
                'nombre' => 'SERVICIOS BÁSICOS',
                'descripcion' => 'PAGOS DE SERVICIOS DE LUZ, AGUA, TELÉFONO, INTERNET',
                'activo' => true
            ],
            [
                'nombre' => 'MATERIALES DE OFICINA',
                'descripcion' => 'COMPRA DE MATERIALES Y SUMINISTROS DE OFICINA',
                'activo' => true
            ],
            [
                'nombre' => 'MANTENIMIENTO',
                'descripcion' => 'SERVICIOS DE MANTENIMIENTO Y REPARACIONES',
                'activo' => true
            ],
            [
                'nombre' => 'VIÁTICOS',
                'descripcion' => 'PAGOS DE VIÁTICOS Y GASTOS DE VIAJE',
                'activo' => true
            ],
            [
                'nombre' => 'HONORARIOS PROFESIONALES',
                'descripcion' => 'PAGOS A PROFESIONALES POR SERVICIOS PRESTADOS',
                'activo' => true
            ],
            [
                'nombre' => 'IMPUESTOS Y TASAS',
                'descripcion' => 'PAGO DE IMPUESTOS, TASAS Y CONTRIBUCIONES',
                'activo' => true
            ],
            [
                'nombre' => 'OTROS GASTOS',
                'descripcion' => 'OTROS GASTOS OPERATIVOS Y ADMINISTRATIVOS',
                'activo' => true
            ]
        ];

        foreach ($conceptos as $concepto) {
            ConceptoPago::create($concepto);
        }
    }
}
