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
                'nombre' => 'Artículo 222',
                'recibos' => 1000,
                'tipo_valor' => 'SVE',
                'valor' => null,
                'descripcion' => 'Libretas de recibos para cobro de servicios de vigilancia por Artículo 222',
                'activo' => true
            ],
            [
                'nombre' => 'Recibos Generales',
                'recibos' => 1000,
                'tipo_valor' => 'SVE',
                'valor' => null,
                'descripcion' => 'Libretas de recibos para cobros varios',
                'activo' => true
            ],
            [
                'nombre' => 'Recibos Generales 1.8 Fondo de Terceros',
                'recibos' => 500,
                'tipo_valor' => 'SVE',
                'valor' => null,
                'descripcion' => 'Libretas de recibos para cobros de 1.8 Fondo de Terceros',
                'activo' => true
            ],
            [
                'nombre' => 'Decreto 391/987',
                'recibos' => 1000,
                'tipo_valor' => 'SVE',
                'valor' => null,
                'descripcion' => 'Libretas de recibos sin valor preestablecido, para cobros varios prehestablecidos',
                'activo' => true
            ],
            [
                'nombre' => 'Porte de armas',
                'recibos' => 500,
                'tipo_valor' => 'UR',
                'valor' => 10.00,
                'descripcion' => 'Libretas de recibos para cobro de porte de armas',
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
            case 'Artículo 222':
                $conceptos = [
                    ['concepto' => 'Art. 222 J.P.M.', 'monto' => 0.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Art. 222 I.N.R.', 'monto' => 0.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Art. 222 D.N.I.I.', 'monto' => 0.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Art. 222 D.N.P.C.', 'monto' => 0.00, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Recibos Generales':
                $conceptos = [
                    ['concepto' => 'Cobro de multas de tránsito varias', 'monto' => 0.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Cobro de multas por carecer de S.O.A.', 'monto' => 0.00, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Recibos Generales 1.8 Fondo de Terceros':
                $conceptos = [
                    ['concepto' => 'Eventuales', 'monto' => 0.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'Arrendamiento', 'monto' => 0.00, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Decreto 391/987':
                $conceptos = [
                    ['concepto' => 'Certificado de Residencia', 'monto' => 0.00, 'tipo_monto' => 'pesos'],
                    ['concepto' => 'T.HA.T.A.', 'monto' => 0.00, 'tipo_monto' => 'pesos']
                ];
                break;

            case 'Porte de armas':
                $conceptos = [
                    ['concepto' => 'Porte de Armas', 'monto' => 10.00, 'tipo_monto' => 'UR']
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
            case 'Artículo 222':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'Memorando 03/2025',
                        'desde' => 1001,
                        'hasta' => 2000,
                        'interno' => null
                    ]
                ];
                break;

            case 'Recibos Generales':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy()->addMonth(),
                        'comprobante' => 'Memorando 25/2025',
                        'desde' => 151001,
                        'hasta' => 152000,
                        'interno' => null
                    ]
                ];
                break;

            case 'Recibos Generales 1.8 Fondo de Terceros':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'Memorando 06/2025',
                        'desde' => 32001,
                        'hasta' => 32500,
                        'interno' => null
                    ]
                ];
                break;

            case 'Decreto 391/987':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'Memorando 06/2025',
                        'desde' => 78001,
                        'hasta' => 79000,
                        'interno' => null
                    ]
                ];
                break;

            case 'Porte de armas':
                $entradas = [
                    [
                        'fecha' => $fechaBase->copy(),
                        'comprobante' => 'Memorando 03/2025',
                        'desde' => 38000,
                        'hasta' => 38500,
                        'interno' => null
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
