<?php

namespace App\Http\Livewire\Tesoreria\Recaudaciones;

use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use Livewire\Component;

class Index extends Component
{
    public $fecha;
    public $filtroMes;
    public $filtroAno;
    public $search = '';

    public function mount()
    {
        $this->fecha = null;
        $this->filtroMes = date('n');
        $this->filtroAno = date('Y');
    }

    public function render()
    {
        $items = TesCfeItem::select('tes_cfe_items.*')
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->with([
                'cfe.cajaConcepto',
                'cfe.siifDistribucionDependencia',
                'cfe.siifDistribucionTipo',
                'cfe.mediosPago',
                'cfe.items',
                'siifDistribucion',
            ])
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereNotNull('tes_cfes.siif_distribucion_tipo_id')
            ->whereNotNull('tes_cfes.siif_distribucion_dependencia_id');

        if ($this->fecha) {
            $items->where('tes_cfes.fecha', $this->fecha);
        } else {
            $items->whereMonth('tes_cfes.fecha', $this->filtroMes)
                  ->whereYear('tes_cfes.fecha', $this->filtroAno);
        }

        if ($this->search !== '') {
            $items->where(function ($q) {
                $term = $this->search;
                $q->where('tes_cfes.documento_tipo', 'like', "%{$term}%")
                  ->orWhere('tes_cfes.documento_serie', 'like', "%{$term}%")
                  ->orWhere('tes_cfes.documento_numero', 'like', "%{$term}%")
                  ->orWhereRaw("CONCAT(tes_cfes.documento_tipo, ' ', tes_cfes.documento_serie, '-', tes_cfes.documento_numero) LIKE ?", ["%{$term}%"]);

                if (is_numeric(str_replace(['.', ','], '', $term))) {
                    $monto = (float) str_replace(',', '.', str_replace('.', '', $term));
                    $q->orWhere('tes_cfes.total_a_pagar', $monto);
                }
            });
        }

        $items = $items->orderBy('tes_cfes.fecha', 'desc')
            ->orderBy('tes_cfes.id', 'desc')
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
                    'fechas' => [],
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
                } elseif (str_contains($tipoStr, 'transferencia') || str_contains($tipoStr, 'siif')) {
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

            $fechaKey = $cfe->fecha?->format('Y-m-d') ?? 'sin-fecha';

            if (!isset($grupos[$tabKey]['fechas'][$fechaKey])) {
                $grupos[$tabKey]['fechas'][$fechaKey] = [
                    'fecha' => $fechaKey,
                    'distribuciones' => [],
                    'total_efectivo' => 0,
                    'total_cheque' => 0,
                    'total_transferencia' => 0,
                    'total_pos' => 0,
                ];
            }

            if (!isset($grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey])) {
                $grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey] = [
                    'concepto' => $distKey,
                    'items' => [],
                    'total_efectivo' => 0,
                    'total_cheque' => 0,
                    'total_transferencia' => 0,
                    'total_pos' => 0,
                ];
            }

            $grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey]['items'][] = $rowData;
            $grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['fechas'][$fechaKey]['distribuciones'][$distKey]['total_pos'] += $pos;
            $grupos[$tabKey]['fechas'][$fechaKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['fechas'][$fechaKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['fechas'][$fechaKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['fechas'][$fechaKey]['total_pos'] += $pos;
            $grupos[$tabKey]['total_efectivo'] += $efectivo;
            $grupos[$tabKey]['total_cheque'] += $cheque;
            $grupos[$tabKey]['total_transferencia'] += $transferencia;
            $grupos[$tabKey]['total_pos'] += $pos;
        }

        $anosRegistrados = TesCfe::whereNotNull('siif_distribucion_tipo_id')
            ->whereNotNull('siif_distribucion_dependencia_id')
            ->whereNotNull('fecha')
            ->whereNull('deleted_at')
            ->selectRaw('YEAR(fecha) as ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();

        $grupos = collect($grupos)->sortBy('label')->toArray();

        foreach ($grupos as &$grupo) {
            krsort($grupo['fechas']);
        }
        unset($grupo);

        return view('livewire.tesoreria.recaudaciones.index', [
            'grupos' => $grupos,
            'anosRegistrados' => $anosRegistrados,
        ])->extends('layouts.app')->section('content');
    }
}
