<?php

namespace App\Http\Livewire\Tesoreria\EstadosRecaudacion;

use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesPlanillaEr;
use Livewire\Component;

class Confirmar extends Component
{
    public TesPlanillaEr $planilla;

    protected $listeners = ['$refresh'];

    public function mount(TesPlanillaEr $planilla)
    {
        $this->planilla = $planilla->load([
            'tipo', 'dependencia',
            'items.cfe.cajaConcepto',
            'items.cfe.siifDistribucionDependencia',
            'items.cfe.siifDistribucionTipo',
            'items.cfe.mediosPago',
            'items.cfe.items',
            'items.siifDistribucion',
        ]);
    }

    public function cambiarDistribucion(int $itemId, int $siifDistribucionId)
    {
        $item = TesCfeItem::findOrFail($itemId);

        if ($item->planilla_er_id !== $this->planilla->id) {
            abort(403);
        }

        $item->update(['siif_distribucion_id' => $siifDistribucionId]);

        $this->planilla = TesPlanillaEr::with([
            'tipo', 'dependencia',
            'items.cfe.cajaConcepto',
            'items.cfe.siifDistribucionDependencia',
            'items.cfe.siifDistribucionTipo',
            'items.cfe.mediosPago',
            'items.cfe.items',
            'items.siifDistribucion',
        ])->find($this->planilla->id);

        $this->dispatchBrowserEvent('swal:toast-success', [
            'text' => 'Distribución SIIF actualizada.',
        ]);
    }

    public function toggleConfirmada()
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyPermission(['tesoreria.supervisar'])) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No tiene permisos para confirmar planillas.',
            ]);
            return;
        }

        $this->planilla->update(['confirmada' => !$this->planilla->confirmada]);
        $this->planilla = $this->planilla->fresh([
            'tipo', 'dependencia',
            'items.cfe.cajaConcepto',
            'items.cfe.siifDistribucionDependencia',
            'items.cfe.siifDistribucionTipo',
            'items.cfe.mediosPago',
            'items.cfe.items',
            'items.siifDistribucion',
        ]);
    }

    public function render()
    {
        $planillaItemIds = $this->planilla->items->pluck('id')->toArray();

        $itemsPorCfe = [];
        foreach ($this->planilla->items as $itemPlanilla) {
            $cfe = $itemPlanilla->cfe;
            if (!$cfe) continue;

            $cfeLabel = "{$cfe->documento_tipo} {$cfe->documento_serie}-{$cfe->documento_numero}";
            if (isset($itemsPorCfe[$cfeLabel])) continue;

            $itemsConFlag = $cfe->items->map(function ($i) use ($planillaItemIds) {
                $i->enPlanilla = in_array($i->id, $planillaItemIds);
                return $i;
            });

            $itemsPorCfe[$cfeLabel] = $itemsConFlag;
        }

        $paresUnicos = [];
        foreach ($this->planilla->items as $item) {
            $cfe = $item->cfe;
            if (!$cfe || !$cfe->siif_distribucion_tipo_id || !$cfe->siif_distribucion_dependencia_id) continue;
            $key = $cfe->siif_distribucion_tipo_id . '-' . $cfe->siif_distribucion_dependencia_id;
            $paresUnicos[$key] = [
                'tipo_id' => $cfe->siif_distribucion_tipo_id,
                'dependencia_id' => $cfe->siif_distribucion_dependencia_id,
            ];
        }

        $opcionesPorTipoDep = [];
        foreach ($paresUnicos as $key => $par) {
            $opcionesPorTipoDep[$key] = SiifDistribucion::where('tipo_id', $par['tipo_id'])
                ->where('dependencia_id', $par['dependencia_id'])
                ->whereNull('deleted_at')
                ->get()
                ->unique(fn($d) => $d->tipo_id . '-' . $d->dependencia_id . '-' . $d->concepto)
                ->values();
        }

        $gruposRecaudacion = $this->calcularGruposRecaudacion();

        $totalGeneral = $this->planilla->items->sum('importe');

        return view('livewire.tesoreria.estados-recaudacion.confirmar', compact(
            'itemsPorCfe', 'opcionesPorTipoDep', 'gruposRecaudacion', 'totalGeneral'
        ))->extends('layouts.app')->section('content');
    }

    private function calcularGruposRecaudacion(): array
    {
        $items = $this->planilla->items;
        $grupos = [];
        $agregados = [];

        foreach ($items as $item) {
            $cfe = $item->cfe;
            if (!$cfe) continue;

            $dep = $cfe->siifDistribucionDependencia;
            $tipo = $cfe->siifDistribucionTipo;
            $tabKey = ($dep?->id ?? 'X') . '-' . ($tipo?->id ?? 'X');
            $label = ($dep?->abreviatura ?? 'S/D') . ' — ' . ($tipo?->tipo ?? 'S/T');
            $distKey = $item->siifDistribucion?->concepto ?? $cfe->cajaConcepto?->caja_concepto ?? 'Sin distribución';
            $cfeKey = $item->tes_cfe_id;
            $uniq = "{$tabKey}|{$distKey}|{$cfeKey}";

            if (!isset($grupos[$tabKey])) {
                $grupos[$tabKey] = [
                    'label' => $label,
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

            $efectivo = 0; $cheque = 0; $transferencia = 0; $pos = 0;

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

        return $grupos;
    }
}
