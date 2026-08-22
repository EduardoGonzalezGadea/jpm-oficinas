<?php

namespace App\Livewire\Tesoreria\PlanillasComunes;

use App\Models\Tesoreria\TesPlanillaComun;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\CajaConcepto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filtroEstado = '';
    public $filtroConcepto = null;
    public array $filtroMeses = [];
    public $filtroAno = 0;

    public $mostrarModalPlanilla = false;
    public $planillaVer = null;
    public Collection $totalesPorInstitucion;

    protected $listeners = ['cerrarModalPlanilla', 'anularPlanilla'];

    public function mount()
    {
        $this->filtroAno = 0;
        $this->totalesPorInstitucion = collect();
    }

    public function limpiarFiltroMeses()
    {
        $this->filtroMeses = [];
    }

    public function limpiarFiltros()
    {
        $this->search = '';
        $this->filtroEstado = '';
        $this->filtroConcepto = null;
        $this->filtroMeses = [];
        $this->filtroAno = 0;
        $this->resetPage();
    }

    public function updatedFiltroAno()
    {
        $this->resetPage();
    }

    public function updatedFiltroMeses()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatedFiltroConcepto()
    {
        $this->resetPage();
    }

    public function verPlanilla(int $id): void
    {
        $this->planillaVer = TesPlanillaComun::withTrashed()->with([
            'cajaConcepto',
            'cfes' => fn($q) => $q->with('institucion')->orderBy('fecha')->orderBy('documento_numero'),
        ])->findOrFail($id);

        // Calcular totales por institución si el concepto requiere institución
        $this->totalesPorInstitucion = collect();
        if ($this->planillaVer->cajaConcepto && $this->planillaVer->cajaConcepto->requiere_institucion) {
            $this->totalesPorInstitucion = $this->planillaVer->cfes
                ->whereNotNull('institucion_id')
                ->groupBy('institucion_id')
                ->map(function ($cfesGrupo) {
                    return (object) [
                        'institucion' => $cfesGrupo->first()->institucion,
                        'total_monto' => $cfesGrupo->sum('total_a_pagar'),
                    ];
                })
                ->sortBy(fn($item) => $item->institucion?->descripcion ?? 'Sin institución')
                ->values();
        }

        $this->mostrarModalPlanilla = true;
        $this->dispatch('abrir-modal-planilla');
    }

    public function cerrarModalPlanilla(): void
    {
        $this->mostrarModalPlanilla = false;
        $this->planillaVer = null;
        $this->totalesPorInstitucion = collect();
        $this->dispatch('cerrar-modal-planilla');
    }

    public function anularPlanilla($id = null, $motivo = null): void
    {
        if (is_array($id)) {
            $motivo = $id['motivo'] ?? $motivo;
            $id = $id['id'] ?? null;
        }

        if (!$id) {
            return;
        }

        try {
            DB::beginTransaction();

            $planilla = TesPlanillaComun::withTrashed()->findOrFail($id);

            $planilla->update([
                'motivo_anulacion' => $motivo,
                'confirmada' => false,
            ]);

            TesCfe::where('planilla_comun_id', $id)
                ->update(['planilla_comun_id' => null]);

            $planilla->delete();

            DB::commit();

            $this->dispatch('swal:success', title: 'Planilla anulada', text: "La planilla {$planilla->numero} ha sido anulada con éxito.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('swal:toast-error', text: 'Error al anular la planilla: ' . $e->getMessage());
        }
    }

    public function toggleConfirmada(int $id): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyPermission(['tesoreria.supervisar'])) {
            $this->dispatch('swal:toast-error', text: 'No tiene permisos para confirmar planillas.');
            return;
        }

        $planilla = TesPlanillaComun::withTrashed()->findOrFail($id);
        $planilla->update(['confirmada' => !$planilla->confirmada]);

        $this->planillaVer = TesPlanillaComun::withTrashed()->with([
            'cajaConcepto',
            'cfes' => fn($q) => $q->with('institucion')->orderBy('fecha')->orderBy('documento_numero'),
        ])->findOrFail($id);

        // Recalcular totales por institución después del toggle
        $this->totalesPorInstitucion = collect();
        if ($this->planillaVer->cajaConcepto && $this->planillaVer->cajaConcepto->requiere_institucion) {
            $this->totalesPorInstitucion = $this->planillaVer->cfes
                ->whereNotNull('institucion_id')
                ->groupBy('institucion_id')
                ->map(function ($cfesGrupo) {
                    return (object) [
                        'institucion' => $cfesGrupo->first()->institucion,
                        'total_monto' => $cfesGrupo->sum('total_a_pagar'),
                    ];
                })
                ->sortBy(fn($item) => $item->institucion?->descripcion ?? 'Sin institución')
                ->values();
        }
    }

    public function render()
    {
        $query = TesPlanillaComun::with(['cajaConcepto', 'cfes']);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('numero', 'like', '%' . $this->search . '%')
                  ->orWhereHas('cajaConcepto', fn($sq) => $sq->where('caja_concepto', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->filtroEstado === 'confirmada') {
            $query->where('confirmada', true);
        } elseif ($this->filtroEstado === 'pendiente') {
            $query->where('confirmada', false);
        } elseif ($this->filtroEstado === 'anulada') {
            $query->onlyTrashed();
        } else {
            $query->withTrashed();
        }

        if ($this->filtroConcepto) {
            $query->where('tes_caja_concepto_id', $this->filtroConcepto);
        }

        if ($this->filtroAno) {
            $query->whereYear('fecha', $this->filtroAno);
        }

        if (!empty($this->filtroMeses)) {
            $query->where(function ($q) {
                foreach ($this->filtroMeses as $mes) {
                    $q->orWhereMonth('fecha', $mes);
                }
            });
        }

        $planillas = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')
            ->paginate(25);

        $planillasPorFecha = $planillas->groupBy(function ($p) {
            return $p->fecha->format('Y-m-d');
        });

        $cajaConceptos = \Illuminate\Support\Facades\Cache::remember('planillas_comunes_conceptos', 300, fn() =>
            CajaConcepto::whereNull('deleted_at')
                ->where('requiere_distribucion', false)
                ->where('permite_planilla', true)
                ->orderBy('caja_concepto')
                ->get()
        );

        $currentYear = (int) date('Y');
        $anosRegistrados = \Illuminate\Support\Facades\Cache::remember('planillas_comunes_anos', 300, function () use ($currentYear) {
            $anos = TesPlanillaComun::withTrashed()->whereNotNull('fecha')
                ->get(['fecha'])
                ->map(fn($p) => (int) $p->fecha->format('Y'))
                ->unique()
                ->sortDesc()
                ->values()
                ->toArray();

            if (!in_array($currentYear, $anos)) {
                array_unshift($anos, $currentYear);
            }

            return $anos;
        });

        return view('livewire.tesoreria.planillas-comunes.index', compact('planillas', 'planillasPorFecha', 'cajaConceptos', 'anosRegistrados'));
    }
}
