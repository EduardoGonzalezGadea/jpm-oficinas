<?php

namespace App\Http\Livewire\Tesoreria\Recaudaciones;

use App\Models\Tesoreria\TesCfeItem;
use Livewire\Component;

class Index extends Component
{
    public $fecha;

    public function mount()
    {
        $this->fecha = date('Y-m-d');
    }

    public function render()
    {
        $items = TesCfeItem::with([
                'cfe.cajaConcepto',
                'cfe.siifDistribucionDependencia',
                'cfe.siifDistribucionTipo',
                'cfe.mediosPago',
                'cfe.items',
                'siifDistribucion',
            ])
            ->whereHas('cfe', fn($q) => $q->where('fecha', $this->fecha)
                ->whereNotNull('siif_distribucion_tipo_id')
                ->whereNotNull('siif_distribucion_dependencia_id')
                ->whereNull('deleted_at'))
            ->whereNull('deleted_at')
            ->get();

        $grupos = [];
        $agregados = [];

        foreach ($items as $item) {
            $cfe = $item->cfe;
            $dep = $cfe->siifDistribucionDependencia;
            $tipo = $cfe->siifDistribucionTipo;
            $tabKey = ($dep?->id ?? 'X') . '-' . ($tipo?->id ?? 'X');
            $label = ($dep?->abreviatura ?? 'S/D') . ' — ' . ($tipo?->tipo ?? 'S/T');
            $distKey = $item->siifDistribucion?->concepto ?? $item->cfe->cajaConcepto?->caja_concepto ?? 'Sin distribución';
            $cfeKey = $item->tes_cfe_id;
            $uniq = "{$tabKey}|{$distKey}|{$cfeKey}";

            if (!isset($grupos[$tabKey])) {
                $grupos[$tabKey] = [
                    'label' => $label,
                    'dependencia' => $dep,
                    'tipo' => $tipo,
                    'distribuciones' => [],
                    'total_efectivo' => 0,
                    'total_cheque' => 0,
                    'total_transferencia' => 0,
                    'total_pos' => 0,
                ];
            }

            if (!isset($grupos[$tabKey]['distribuciones'][$distKey])) {
                $grupos[$tabKey]['distribuciones'][$distKey] = [
                    'concepto' => $distKey,
                    'items' => [],
                    'total_efectivo' => 0,
                    'total_cheque' => 0,
                    'total_transferencia' => 0,
                    'total_pos' => 0,
                ];
            }

            if (!isset($agregados[$uniq])) {
                $agregados[$uniq] = [
                    'cfe' => $cfe,
                    'sumImporte' => 0,
                ];
            }
            $agregados[$uniq]['sumImporte'] += $item->importe;
        }

        foreach ($agregados as $uniq => $aggr) {
            [$tabKey, $distKey, $cfeId] = explode('|', $uniq, 3);
            $cfe = $aggr['cfe'];
            $sumImporte = $aggr['sumImporte'];

            $cfeTotalItems = $cfe->items->sum('importe');
            $proporcion = $cfeTotalItems > 0 ? $sumImporte / $cfeTotalItems : 0;

            $efectivo = 0;
            $cheque = 0;
            $transferencia = 0;
            $pos = 0;

            foreach ($cfe->mediosPago as $mp) {
                $tipoStr = mb_strtolower($mp->medio_pago_tipo);
                $valorProrated = round($mp->medio_pago_valor * $proporcion, 2);

                if (str_contains($tipoStr, 'efectivo')) {
                    $efectivo += $valorProrated;
                } elseif (str_contains($tipoStr, 'cheque')) {
                    $cheque += $valorProrated;
                } elseif (str_contains($tipoStr, 'transferencia')) {
                    $transferencia += $valorProrated;
                } elseif (str_contains($tipoStr, 'tarjeta') || str_contains($tipoStr, 'debito') || str_contains($tipoStr, 'débito')) {
                    $pos += $valorProrated;
                }
            }

            $rowData = [
                'cfe' => $cfe,
                'efectivo' => $efectivo,
                'cheque' => $cheque,
                'transferencia' => $transferencia,
                'pos' => $pos,
            ];

            $grupos[$tabKey]['distribuciones'][$distKey]['items'][] = $rowData;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['distribuciones'][$distKey]['total_pos'] += $pos;
            $grupos[$tabKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['total_pos'] += $pos;
        }

        return view('livewire.tesoreria.recaudaciones.index', [
            'grupos' => $grupos,
        ])->extends('layouts.app')->section('content');
    }
}
