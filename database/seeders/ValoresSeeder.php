<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\Valores\Valor;
use App\Models\Tesoreria\Valores\ValorConcepto;
use App\Models\Tesoreria\Valores\ValorEntrada;
use Carbon\Carbon;

class ValoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear valores de ejemplo
        $valores = [
            [
                'nombre' => 'Recibos de Agua',
                'recibos' => 100,
                'tipo_valor' => 'pesos',
                'valor' => 150.00,
                'descripcion' => 'Libretas de recibos para cobro de servicio de agua potable',
                'activo' => true
            ],
            [
                'nombre' => 'Recibos de Saneamiento',
                'recibos' => 100,
                'tipo_valor' => 'pesos',
                'valor' => 200.00,
                'descripcion' => 'Libretas de recibos para cobro de servicio de saneamiento',
                'activo' => true
            ],
            [
                'nombre' => 'Recibos Unidad Reajustable',
                'recibos' => 50,
                'tipo_valor' => 'UR',
                'valor' => 5.25,
                'descripcion' => 'Libretas de recibos con valor en unidades reajustables',
                'activo' => true
            ],
            [
                'nombre' => 'Recibos Sin Valor',
                'recibos' => 100,
                'tipo_valor' => 'SVE',
                'valor' => null,
                'descripcion' => 'Libretas de recibos sin valor preestablecido',
                'activo' => true
            ],
            [
                'nombre' => 'Recibos de Multas',
                'recibos' => 50,
                'tipo_valor' => 'pesos',
                'valor' => 500.00,
                'descripcion' => 'Libretas de recibos para cobro de multas administrativas',
                'activo' => true
            ]
        ];

        foreach ($valores as $valorData) {
            $valor = Valor::create($valorData);

            // Crear conceptos para cada valor
            $this->crearConceptos($valor);

            // Crear algunas entradas de ejemplo
            $this->crearEntradas($valor);
        }
    }

    private function crearConceptos(Valor $valor)
    {
        $conceptos = [];

        switch ($valor->nombre) {
            case 'Recibos de Agua':
                $conceptos = [
                    ['concepto' => 'Cobro Residencial', 'monto' => 150.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Cobro Comercial', 'monto' => 300.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Cobro Industrial', 'monto' => 500.00, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Recibos de Saneamiento':
                $conceptos = [
                    ['concepto' => 'Saneamiento Residencial', 'monto' => 200.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Saneamiento Comercial', 'monto' => 400.00, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Recibos Unidad Reajustable':
                $conceptos = [
                    ['concepto' => 'Servicios en UR', 'monto' => 5.25, 'tipo_monto' => 'UR'],
                    ['concepto' => 'Tasas Municipales', 'monto' => 3.50, 'tipo_monto' => 'UR']
                ];
                break;

            case 'Recibos Sin Valor':
                $conceptos = [
                    ['concepto' => 'Servicios Varios', 'monto' => 0, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Cobros Especiales', 'monto' => 0, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Recibos de Multas':
                $conceptos = [
                    ['concepto' => 'Multas de Tránsito', 'monto' => 500.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Multas Administrativas', 'monto' => 750.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Multas Ambientales', 'monto' => 1000.00, 'tipo_monto' => 'pesos']
                ];
                break;
        }

        foreach ($conceptos as $conceptoData) {
            ValorConcepto::create([
                'valores_id' => $valor->id,
                'concepto' => $conceptoData['concepto'],
                'monto' => $conceptoData['monto'],
                'tipo_monto' => $conceptoData['tipo_monto'],
                'descripcion' => 'Concepto para ' . $conceptoData['concepto'],
                'activo' => true
            ]);
        }
    }

    private function crearEntradas(Valor $valor)
    {
        $entradas = [];
        $fechaBase = Carbon::now()->subMonths(6);

        switch ($valor->nombre) {
            case 'Recibos de Agua':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'MEM-001/2024',
                        'desde' => 1001,
                        'hasta' => 1500,
                        'interno' => 'LIB-001'
                    ],
                    [
                        'fecha' => $fechaBase->copy()->addMonth(),
                        'comprobante' => 'MEM-015/2024',
                        'desde' => 1501,
                        'hasta' => 2000,
                        'interno' => 'LIB-002'
                    ],
                    [
                        'fecha' => $fechaBase->copy()->addMonths(2),
                        'comprobante' => 'MEM-032/2024',
                        'desde' => 2001,
                        'hasta' => 2300,
                        'interno' => 'LIB-003'
                    ]
                ];
                break;

            case 'Recibos de Saneamiento':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'MEM-002/2024',
                        'desde' => 5001,
                        'hasta' => 5400,
                        'interno' => 'SAN-001'
                    ],
                    [
                        'fecha' => $fechaBase->copy()->addMonth(),
                        'comprobante' => 'MEM-018/2024',
                        'desde' => 5401,
                        'hasta' => 5700,
                        'interno' => 'SAN-002'
                    ]
                ];
                break;

            case 'Recibos Unidad Reajustable':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'MEM-005/2024',
                        'desde' => 3001,
                        'hasta' => 3200,
                        'interno' => 'UR-001'
                    ]
                ];
                break;

            case 'Recibos Sin Valor':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'MEM-008/2024',
                        'desde' => 4001,
                        'hasta' => 4300,
                        'interno' => 'SVE-001'
                    ]
                ];
                break;

            case 'Recibos de Multas':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'MEM-010/2024',
                        'desde' => 6001,
                        'hasta' => 6150,
                        'interno' => 'MUL-001'
                    ]
                ];
                break;
        }

        foreach ($entradas as $entradaData) {
            ValorEntrada::create([
                'valores_id' => $valor->id,
                'fecha' => $entradaData['fecha'],
                'comprobante' => $entradaData['comprobante'],
                'desde' => $entradaData['desde'],
                'hasta' => $entradaData['hasta'],
                'interno' => $entradaData['interno'],
                'observaciones' => 'Entrada inicial de libretas - ' . $valor->nombre
            ]);
        }
    }
}
