<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\Cajas\Concepto;

class CajaConceptoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conceptos = [
            // Ingresos
            ['nombre' => 'Venta de Timbres', 'tipo' => 'INGRESO', 'descripcion' => 'Ingreso por venta de timbres fiscales.'],
            ['nombre' => 'Cobro de Tasas Administrativas', 'tipo' => 'INGRESO', 'descripcion' => 'Ingreso por cobro de diversas tasas administrativas.'],
            ['nombre' => 'Reintegro de Viáticos', 'tipo' => 'INGRESO', 'descripcion' => 'Reintegro de viáticos no utilizados por funcionarios.'],
            ['nombre' => 'Otros Ingresos', 'tipo' => 'INGRESO', 'descripcion' => 'Otros ingresos no especificados.'],

            // Egresos
            ['nombre' => 'Pago a Proveedores', 'tipo' => 'EGRESO', 'descripcion' => 'Pago de facturas a proveedores de bienes o servicios.'],
            ['nombre' => 'Adelanto de Sueldo', 'tipo' => 'EGRESO', 'descripcion' => 'Adelanto de sueldo a funcionarios.'],
            ['nombre' => 'Compra de Insumos de Oficina', 'tipo' => 'EGRESO', 'descripcion' => 'Compra de papelería y otros insumos de oficina.'],
            ['nombre' => 'Pago de Viáticos', 'tipo' => 'EGRESO', 'descripcion' => 'Entrega de viáticos a funcionarios para misiones oficiales.'],
            ['nombre' => 'Otros Egresos', 'tipo' => 'EGRESO', 'descripcion' => 'Otros egresos no especificados.'],
        ];

        foreach ($conceptos as $concepto) {
            Concepto::create($concepto);
        }
    }
}
