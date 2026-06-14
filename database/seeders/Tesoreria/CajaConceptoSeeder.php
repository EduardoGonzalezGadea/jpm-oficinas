<?php

namespace Database\Seeders\Tesoreria;

use Illuminate\Database\Seeder;
use App\Models\Tesoreria\CajaConcepto;

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
            [
                'id'                        => 1,
                'caja_concepto'             => 'ARRENDAMIENTOS',
                'requiere_confirmacion'     => true,
                'requiere_distribucion'     => false,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 2,
            ],
            [
                'id'                        => 2,
                'caja_concepto'             => 'ARTÍCULO 222',
                'requiere_confirmacion'     => true,
                'requiere_distribucion'     => true,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 1,
            ],
            [
                'id'                        => 3,
                'caja_concepto'             => 'CERTIFICADO DE RESIDENCIA',
                'requiere_confirmacion'     => false,
                'requiere_distribucion'     => true,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 2,
            ],
            [
                'id'                        => 4,
                'caja_concepto'             => 'MULTAS DE TRÁNSITO',
                'requiere_confirmacion'     => false,
                'requiere_distribucion'     => true,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 2,
            ],
            [
                'id'                        => 5,
                'caja_concepto'             => 'PORTE DE ARMAS',
                'requiere_confirmacion'     => false,
                'requiere_distribucion'     => true,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 2,
            ],
            [
                'id'                        => 6,
                'caja_concepto'             => 'TITULO HABILITACIÓN Y TENENCIA DE ARMA (TAHTA)',
                'requiere_confirmacion'     => false,
                'requiere_distribucion'     => true,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 2,
            ],
            [
                'id'                        => 7,
                'caja_concepto'             => 'DEPÓSITO DE VEHÍCULOS',
                'requiere_confirmacion'     => false,
                'requiere_distribucion'     => true,
                'permite_planilla'          => true,
                'siif_distribucion_tipo_id' => 2,
            ],
        ];

        foreach ($conceptos as $concepto) {
            CajaConcepto::updateOrCreate(['id' => $concepto['id']], $concepto);
        }
    }
}
