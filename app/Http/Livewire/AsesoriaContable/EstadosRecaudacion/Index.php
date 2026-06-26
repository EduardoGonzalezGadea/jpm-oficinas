<?php

namespace App\Http\Livewire\AsesoriaContable\EstadosRecaudacion;

use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\TesPlanillaEr;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public array $filtroMeses = [];
    public $filtroAno = null;

    public $mostrarModalDetalles = false;
    public $planillaDetalles = null;
    public $mostrarModalPlanilla = false;
    public $planillaVer = null;

    protected $listeners = ['cerrarModalDetalles', 'cerrarModalPlanilla'];

    public function mount()
    {
        $this->filtroAno = (int) date('Y');
        $this->filtroMeses = [(int) date('m')];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFiltroMeses()
    {
        $this->resetPage();
    }

    public function updatedFiltroAno()
    {
        $this->resetPage();
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
    }

    public function verDetalles(int $id): void
    {
        $this->planillaDetalles = TesPlanillaEr::with([
            'tipo',
            'dependencia',
            'items.cfe',
            'items.siifDistribucion',
        ])->findOrFail($id);

        $this->mostrarModalDetalles = true;
        $this->dispatchBrowserEvent('abrir-modal-detalles');
    }

    public function cerrarModalDetalles(): void
    {
        $this->dispatchBrowserEvent('cerrar-modal-detalles');
        $this->mostrarModalDetalles = false;
        $this->planillaDetalles = null;
    }

    public function verPlanilla(int $id): void
    {
        $this->planillaVer = TesPlanillaEr::with([
            'tipo', 'dependencia',
            'items.cfe',
            'items.siifDistribucion',
        ])->findOrFail($id);

        $this->mostrarModalPlanilla = true;
        $this->dispatchBrowserEvent('abrir-modal-planilla');
    }

    public function cerrarModalPlanilla(): void
    {
        $this->dispatchBrowserEvent('cerrar-modal-planilla');
        $this->mostrarModalPlanilla = false;
        $this->planillaVer = null;
    }

    public function toggleConfirmada(int $id): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyPermission(['asesoria_contable.supervisar'])) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'No tiene permisos para confirmar planillas.',
            ]);
            return;
        }

        $planilla = TesPlanillaEr::findOrFail($id);
        $planilla->update(['confirmada' => !$planilla->confirmada]);

        $this->planillaVer = TesPlanillaEr::with([
            'tipo', 'dependencia',
            'items.cfe',
            'items.siifDistribucion',
        ])->findOrFail($id);
    }

    public function render()
    {
        $anosRegistrados = TesPlanillaEr::whereNotNull('fecha')
            ->selectRaw('YEAR(fecha) as year')
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values()
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $anosRegistrados)) {
            array_unshift($anosRegistrados, $currentYear);
        }

        $query = TesPlanillaEr::with(['tipo', 'dependencia', 'items.siifDistribucion'])
            ->whereNotNull('er_numero')
            ->whereNotNull('transferencia_fecha')
            ->whereNotNull('transferencia_confirmacion')
            ->when($this->search, function ($query) {
                $query->where('numero', 'like', '%' . $this->search . '%')
                    ->orWhereHas('items.cfe', fn($q) => $q->where('documento_numero', 'like', '%' . $this->search . '%'));
            })
            ->when($this->filtroAno, fn($q) => $q->whereYear('fecha', $this->filtroAno))
            ->when(!empty($this->filtroMeses), function ($q) {
                $q->where(function ($query) {
                    foreach ($this->filtroMeses as $mes) {
                        $query->orWhereMonth('fecha', (int) $mes);
                    }
                });
            })
            ->orderBy('fecha', 'desc')->orderBy('id', 'desc');

        $todasPlanillas = $query->get();
        $totalesAjustados = $this->calcularTotalesAjustados($todasPlanillas);

        $dailyTotals = [];
        $lastPerDate = [];
        foreach ($todasPlanillas as $p) {
            $fechaKey = $p->fecha ? $p->fecha->format('Y-m-d') : '0000-00-00';
            $monto = $totalesAjustados[$p->id] ?? 0;
            $dailyTotals[$fechaKey] = ($dailyTotals[$fechaKey] ?? 0) + $monto;
            $lastPerDate[$fechaKey] = $p->id;
        }

        $planillas = $query->paginate(15);

        $grupos = [];
        foreach ($planillas as $p) {
            $fechaKey = $p->fecha ? $p->fecha->format('Y-m-d') : '0000-00-00';
            $fechaDisplay = $p->fecha ? $p->fecha->format('d/m/Y') : 'Sin fecha';
            if (!isset($grupos[$fechaKey])) {
                $grupos[$fechaKey] = [
                    'fecha_display' => $fechaDisplay,
                    'total_dia' => $dailyTotals[$fechaKey] ?? 0,
                    'ultimo_id' => $lastPerDate[$fechaKey] ?? null,
                    'mostrar_total' => false,
                    'planillas' => [],
                ];
            }
            $grupos[$fechaKey]['planillas'][] = $p;
        }

        foreach ($grupos as $fechaKey => &$grupo) {
            $lastInPage = $grupo['planillas'][count($grupo['planillas']) - 1] ?? null;
            $grupo['mostrar_total'] = $lastInPage && $lastInPage->id === $grupo['ultimo_id'];
        }
        unset($grupo);

        return view('livewire.asesoria-contable.estados-recaudacion', compact('planillas', 'anosRegistrados', 'totalesAjustados', 'grupos'))
            ->extends('layouts.app')
            ->section('content');
    }

    private function calcularTotalesAjustados($planillas): array
    {
        $combos = [];
        foreach ($planillas as $p) {
            foreach ($p->items as $item) {
                if ($item->siifDistribucion) {
                    $sd = $item->siifDistribucion;
                    $key = $sd->tipo_id . '|' . $sd->dependencia_id . '|' . ($sd->concepto ?? '');
                    $combos[$key] = ['tipo_id' => $sd->tipo_id, 'dependencia_id' => $sd->dependencia_id, 'concepto' => $sd->concepto];
                }
            }
        }

        $distribucionesPorCombo = collect();
        if (!empty($combos)) {
            $distribucionesPorCombo = SiifDistribucion::whereNull('deleted_at')
                ->where(function ($q) use ($combos) {
                    $first = true;
                    foreach ($combos as $key => $c) {
                        if (empty($c['concepto'])) {
                            continue;
                        }
                        if ($first) {
                            $q->where('tipo_id', $c['tipo_id'])
                              ->where('dependencia_id', $c['dependencia_id'])
                              ->where('concepto', $c['concepto']);
                            $first = false;
                        } else {
                            $q->orWhere(function ($q2) use ($c) {
                                $q2->where('tipo_id', $c['tipo_id'])
                                   ->where('dependencia_id', $c['dependencia_id'])
                                   ->where('concepto', $c['concepto']);
                            });
                        }
                    }
                })
                ->get()
                ->groupBy(fn($d) => $d->tipo_id . '|' . $d->dependencia_id . '|' . ($d->concepto ?? ''));
        }

        $totales = [];
        foreach ($planillas as $p) {
            $itemsPorConcepto = $p->items->groupBy(fn($i) => $i->siifDistribucion?->concepto ?? 'Sin distribución');
            $total = 0;
            foreach ($itemsPorConcepto as $concepto => $items) {
                $grupoTotal = $items->sum('importe');
                if ($concepto !== 'Sin distribución' && $items->first()->siifDistribucion) {
                    $sd = $items->first()->siifDistribucion;
                    $key = $sd->tipo_id . '|' . $sd->dependencia_id . '|' . $sd->concepto;
                    $distGrupo = $distribucionesPorCombo->get($key, collect());
                    if ($distGrupo->isNotEmpty()) {
                        $dg = $distGrupo->groupBy(fn($d) => ($d->financiacion ?? '—') . '|' . ($d->inciso ?? '—') . '|' . ($d->unidad_ejecutora ?? '—'))
                            ->map(function ($g) use ($grupoTotal) {
                                $primer = $g->first();
                                return (object) [
                                    'importe_raw' => $grupoTotal * ($g->sum('porcentaje') / 100),
                                    'inciso' => $primer->inciso,
                                    'unidad_ejecutora' => $primer->unidad_ejecutora,
                                ];
                            });

                        $sumaRedondeada = 0;
                        foreach ($dg as $d) {
                            $d->importe = round($d->importe_raw, 0);
                            $sumaRedondeada += $d->importe;
                        }

                        $diferencia = round($grupoTotal - $sumaRedondeada, 0);
                        if ($diferencia != 0) {
                            $compensado = false;
                            foreach ($dg as $d) {
                                if ($d->unidad_ejecutora == '4' && $d->inciso == '1') {
                                    $d->importe = round($d->importe + $diferencia, 0);
                                    $compensado = true;
                                    break;
                                }
                            }
                            if (!$compensado) {
                                $d = $dg->first();
                                if ($d) {
                                    $d->importe = round($d->importe + $diferencia, 0);
                                }
                            }
                        }

                        $total += $dg->sum('importe');
                        continue;
                    }
                }
                $total += $grupoTotal;
            }
            $totales[$p->id] = $total;
        }
        return $totales;
    }
}
