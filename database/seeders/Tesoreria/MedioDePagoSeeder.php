<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tesoreria\MedioDePago;

class MedioDePagoSeeder extends Seeder
{
    public function run(): void
    {
        $mediosDePago = [
            [
                'nombre' => 'Efectivo',
                'nombre_corto' => 'Efectivo',
                'descripcion' => 'Dinero físico en billetes/monedas',
                'contado' => true,
                'es_libro_diario' => true,
                'es_recaudacion' => true,
                'orden' => 1,
                'activo' => true,
            ],
            [
                'nombre' => 'Cheque',
                'nombre_corto' => 'Cheque',
                'descripcion' => 'Cheque bancario (propios o de terceros)',
                'contado' => false,
                'es_libro_diario' => true,
                'es_recaudacion' => true,
                'orden' => 2,
                'activo' => true,
            ],
            [
                'nombre' => 'Transferencia Bancaria',
                'nombre_corto' => 'Transferencia',
                'descripcion' => 'Transferencia entre cuentas (BROU, otra)',
                'contado' => false,
                'es_libro_diario' => true,
                'es_recaudacion' => true,
                'orden' => 3,
                'activo' => true,
            ],
            [
                'nombre' => 'Tarjeta de Débito',
                'nombre_corto' => 'Débito (POS)',
                'descripcion' => 'Tarjeta de débito terminal POS',
                'contado' => false,
                'es_libro_diario' => true,
                'es_recaudacion' => true,
                'orden' => 4,
                'activo' => true,
            ],
        ];

        foreach ($mediosDePago as $medio) {
            MedioDePago::firstOrCreate(
                ['nombre' => $medio['nombre']],
                $medio
            );
        }
    }
}
