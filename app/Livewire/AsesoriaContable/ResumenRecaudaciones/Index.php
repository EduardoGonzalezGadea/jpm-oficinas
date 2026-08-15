<?php

namespace App\Livewire\AsesoriaContable\ResumenRecaudaciones;

use App\Models\Tesoreria\SiifDistribucionTipo;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.app')]
class Index extends Component
{
    public $search = '';
    public array $filtroMeses = [];
    public $filtroAno = null;
    public $dependencia_id = '';
    public $tipo_id = '';
    public $monto_desde = '';
    public $monto_hasta = '';

    #[Computed]
    public function opcionesDependencias()
    {
        return SiifDistribucionDependencia::orderBy('dependencia')->get();
    }

    #[Computed]
    public function opcionesTipos()
    {
        return SiifDistribucionTipo::orderBy('tipo')->get();
    }

    public function mount()
    {
        $this->filtroAno = (int) date('Y');
        $this->filtroMeses = [(int) date('m')];
    }

    public function limpiarFiltroMeses(): void
    {
        $this->filtroMeses = [];
    }

    public function resetearBusqueda(): void
    {
        $this->search = '';
        $this->filtroMeses = [];
        $this->filtroAno = (int) date('Y');
        $this->dependencia_id = '';
        $this->tipo_id = '';
        $this->monto_desde = '';
        $this->monto_hasta = '';
    }

    public function render()
    {
        $items = TesCfeItem::select('tes_cfe_items.*')
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->join('tes_caja_conceptos', 'tes_cfes.tes_caja_concepto_id', '=', 'tes_caja_conceptos.id')
            ->leftJoin('siif_distribucions', 'tes_cfe_items.siif_distribucion_id', '=', 'siif_distribucions.id')
            ->with([
                'cfe.cajaConcepto',
                'cfe.siifDistribucionDependencia',
                'cfe.cajaConcepto.siifDistribucionTipo',
                'cfe.mediosPago',
                'cfe.items',
                'siifDistribucion.tipo',
                'siifDistribucion.dependencia',
            ])
            ->whereNull('tes_cfe_items.deleted_at')
            ->whereNull('tes_cfes.deleted_at')
            ->whereNotNull('tes_caja_conceptos.siif_distribucion_tipo_id')
            ->whereNotNull('tes_cfes.siif_distribucion_dependencia_id');

        if ($this->filtroAno && (int) $this->filtroAno !== 0) {
            $items->whereYear('tes_cfes.fecha', (int) $this->filtroAno);
        }

        if (!empty($this->filtroMeses)) {
            $items->where(function ($q) {
                foreach ($this->filtroMeses as $mes) {
                    $q->orWhereMonth('tes_cfes.fecha', (int) $mes);
                }
            });
        }

        if ($this->search !== '') {
            $items->where(function ($q) {
                $term = $this->search;
                $q->where('tes_cfes.documento_tipo', 'like', "%{$term}%")
                  ->orWhere('tes_cfes.documento_serie', 'like', "%{$term}%")
                  ->orWhere('tes_cfes.documento_numero', 'like', "%{$term}%")
                  ->orWhere('tes_cfes.receptor_nombre_denominacion', 'like', "%{$term}%")
                  ->orWhereRaw("CONCAT(tes_cfes.documento_tipo, ' ', tes_cfes.documento_serie, '-', tes_cfes.documento_numero) LIKE ?", ["%{$term}%"]);

                if (is_numeric(str_replace(['.', ','], '', $term))) {
                    $monto = (float) str_replace(',', '.', str_replace('.', '', $term));
                    $q->orWhere('tes_cfes.total_a_pagar', $monto);
                }
            });
        }

        if ($this->dependencia_id !== '') {
            $items->where('tes_cfes.siif_distribucion_dependencia_id', $this->dependencia_id);
        }

        if ($this->tipo_id !== '') {
            $items->where('tes_caja_conceptos.siif_distribucion_tipo_id', $this->tipo_id);
        }

        if ($this->monto_desde !== '') {
            $items->where('tes_cfes.total_a_pagar', '>=', (float) $this->monto_desde);
        }

        if ($this->monto_hasta !== '') {
            $items->where('tes_cfes.total_a_pagar', '<=', (float) $this->monto_hasta);
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
                } elseif (str_contains($tipoStr, 'tarjeta') || str_contains($tipoStr, 'debito') || str_contains($tipoStr, 'débito')) {
                    $pos += $valorProrated;
                } else {
                    // Transferencia Bancaria, SIIF, Cancelación de factura y cualquier otro tipo no reconocido
                    $transferencia += $valorProrated;
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
                    'distribucion' => $distKey,
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

        $grupos = collect($grupos)->sortBy('label')->all();

        foreach ($grupos as &$grupo) {
            krsort($grupo['fechas']);
        }
        unset($grupo);

        $anosRegistrados = TesCfe::join('tes_caja_conceptos', 'tes_cfes.tes_caja_concepto_id', '=', 'tes_caja_conceptos.id')
            ->whereNotNull('tes_caja_conceptos.siif_distribucion_tipo_id')
            ->whereNotNull('tes_cfes.siif_distribucion_dependencia_id')
            ->whereNotNull('tes_cfes.fecha')
            ->whereNull('tes_cfes.deleted_at')
            ->selectRaw('YEAR(tes_cfes.fecha) as ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano');

        return view('livewire.asesoria-contable.resumen-recaudaciones', [
            'grupos' => $grupos,
            'anosRegistrados' => $anosRegistrados,
        ]);
    }
}
