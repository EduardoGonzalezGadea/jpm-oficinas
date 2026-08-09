<?php

namespace Database\Seeders;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Database\Seeder;

class LibroDiarioSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTipos();
        $this->seedMedios();
        $this->seedConceptosYDetalles();
    }

    private function seedTipos(): void
    {
        LbTipo::withoutEvents(function () {
            LbTipo::upsert([
                ['nombre' => 'Entrada', 'signo' => 1],
                ['nombre' => 'Salida', 'signo' => -1],
            ], ['nombre'], ['signo']);

            LbTipo::where('nombre', 'Redistribución')->whereNull('deleted_at')->update(['deleted_at' => now()]);
        });
    }

    private function seedMedios(): void
    {
        $medios = [
            ['nombre' => 'Efectivo', 'nombre_corto' => 'Efectivo', 'contado' => true, 'orden' => 1],
            ['nombre' => 'Cheque', 'nombre_corto' => 'Cheque', 'orden' => 2],
            ['nombre' => 'Transferencia Bancaria', 'nombre_corto' => 'Transferencia', 'orden' => 3],
            ['nombre' => 'Tarjeta de Débito (POS)', 'nombre_corto' => 'Débito (POS)', 'orden' => 4],
        ];

        foreach ($medios as $medio) {
            MedioDePago::withoutEvents(function () use ($medio) {
                MedioDePago::firstOrCreate(
                    ['nombre' => $medio['nombre']],
                    [
                        'nombre_corto' => $medio['nombre_corto'],
                        'contado' => $medio['contado'] ?? false,
                        'orden' => $medio['orden'] ?? 99,
                        'activo' => true,
                        'es_libro_diario' => true,
                        'es_recaudacion' => true,
                        'descripcion' => '',
                    ]
                );
            });
        }
    }

    private function seedConceptosYDetalles(): void
    {
        $conceptos = [
            'Partida Presupuestal' => [
                'Estimativo pagos varios',
                'Estimativo giros',
            ],
            'Recaudación Artículo 222' => [
                'Hora hombre normal',
                'Hora hombre nornal (nocturno)',
                'Hora hombre financiero',
                'Hora hombre financiero (nocturno)',
                'Recaudaciones varias de Artículo 222',
            ],
            'Recaudación Diaria' => [
                'Arrendamientos',
                'Certificado de Residencia',
                'Depósito de vehículos',
                'Eventuales',
                'Multas de Tránsito',
                'Multas por carecer de SOA',
                'Prendas',
                'Título de Habilitación y Tenencia de Armas (THATA)',
                'Porte de armas',
                'Otras recaudaciones varias',
            ],
            'Caja Chica' => [
                'Fondo Fijo',
                'Pendiente',
                'Pagos',
            ],
            'Boletos en ventanilla' => [
                'Sueldo Presupuestado',
                'Retención Judicial de Sueldo Presupuestado',
                'Sueldo Presupuestado (rechazo BROU)',
                'Sueldo Presupuestado (con quitas)',
                'Retención Judicial de Sueldo Presupuestado (rechazo BROU)',
                'Retención Judicial de Sueldo Presupuestado (con quitas)',
                'Devolución de mes y años anteriores',
            ],
            'Giros' => [
                'Varios',
            ],
            'Devoluciones' => [
                'Devolución de multas de tránsito',
                'Devolución de multas SOA',
                'Devolución por cobro en demasía',
                'Devolución de cobro indebido',
            ],
            'Pagos varios' => [
                'Pago de servicio',
                'Pago de multa',
                'Pago a proveedores',
            ],
            'Custodia' => [
                'Fondo de comedores',
            ],
        ];

        foreach ($conceptos as $conceptoNombre => $detalles) {
            $concepto = LbConcepto::withoutEvents(function () use ($conceptoNombre) {
                return LbConcepto::firstOrCreate(['nombre' => $conceptoNombre]);
            });

            foreach ($detalles as $detalleNombre) {
                LbDetalle::withoutEvents(function () use ($concepto, $detalleNombre) {
                    LbDetalle::firstOrCreate([
                        'concepto_id' => $concepto->id,
                        'nombre' => $detalleNombre,
                    ]);
                });
            }
        }
    }
}
