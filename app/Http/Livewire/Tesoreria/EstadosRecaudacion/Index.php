<?php

namespace App\Http\Livewire\Tesoreria\EstadosRecaudacion;

use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesPlanillaEr;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $fecha;
    public $fechaPlanilla;
    public $mostrarModalNueva = false;
    public $grupos = [];
    public $seleccionados = [];
    public $grupoActivo = null;
    public $mostrarModalDetalles = false;
    public $planillaDetalles = null;
    public $modoAgrupacionDetalles = 'distribucion';
    public $mostrarModalPlanilla = false;
    public $planillaVer = null;
    public $mostrarModalEditar = false;
    public $planillaEditar = null;
    public $edit_er_numero;
    public $edit_egresos_numero;
    public $edit_ingresos_numero;
    public $edit_transferencia_fecha;
    public $edit_transferencia_confirmacion;

    protected $listeners = ['cerrarModalNueva', 'cerrarModalDetalles', 'cerrarModalPlanilla', 'cerrarModalEditar', 'borrarPlanilla'];

    public function mount()
    {
        $this->fecha = null;
    }

    public function abrirModalNueva()
    {
        $maxFecha = TesCfeItem::whereNull('planilla_er_id')
            ->whereHas('cfe.cajaConcepto', fn($q) => $q->where('requiere_distribucion', true))
            ->whereHas('cfe', fn($q) => $q->whereNotNull('siif_distribucion_tipo_id'))
            ->whereHas('cfe', fn($q) => $q->whereNotNull('fecha'))
            ->join('tes_cfes', 'tes_cfe_items.tes_cfe_id', '=', 'tes_cfes.id')
            ->max('tes_cfes.fecha');

        $this->fechaPlanilla = $maxFecha ?? date('Y-m-d');
        $this->seleccionados = [];
        $this->grupoActivo = null;
        $this->cargarGrupos();
        $this->mostrarModalNueva = true;
        $this->dispatchBrowserEvent('abrir-modal-nueva-planilla');
    }

    public function cerrarModalNueva()
    {
        $this->dispatchBrowserEvent('cerrar-modal-nueva');
        $this->mostrarModalNueva = false;
        $this->seleccionados = [];
        $this->grupoActivo = null;
        $this->grupos = [];
    }

    public function toggleItem(string $grupoKey, int $itemId)
    {
        if ($this->grupoActivo !== null && $this->grupoActivo !== $grupoKey) {
            return;
        }

        $idx = array_search($itemId, $this->seleccionados[$grupoKey] ?? []);
        if ($idx !== false) {
            unset($this->seleccionados[$grupoKey][$idx]);
            $this->seleccionados[$grupoKey] = array_values($this->seleccionados[$grupoKey]);
        } else {
            $this->seleccionados[$grupoKey][] = $itemId;
        }

        if (empty($this->seleccionados[$grupoKey])) {
            unset($this->seleccionados[$grupoKey]);
            $this->grupoActivo = null;
        } else {
            $this->grupoActivo = $grupoKey;
        }
    }

    public function toggleGrupo(string $grupoKey)
    {
        $itemsIds = array_column($this->grupos[$grupoKey]['items'], 'id');

        if (($this->seleccionados[$grupoKey] ?? []) === $itemsIds) {
            unset($this->seleccionados[$grupoKey]);
            $this->grupoActivo = null;
        } else {
            $this->seleccionados = [$grupoKey => $itemsIds];
            $this->grupoActivo = $grupoKey;
        }
    }

    public function toggleFecha(string $grupoKey, string $fecha)
    {
        $grupo = $this->grupos[$grupoKey] ?? null;
        if (!$grupo) return;

        $fechaItemIds = [];
        foreach ($grupo['items'] as $item) {
            if (($item['fecha_cfe'] ?? '') === $fecha) {
                $fechaItemIds[] = $item['id'];
            }
        }

        if (empty($fechaItemIds)) return;

        $selected = $this->seleccionados[$grupoKey] ?? [];
        $allSelected = empty(array_diff($fechaItemIds, $selected));

        if ($allSelected) {
            $this->seleccionados[$grupoKey] = array_values(array_diff($selected, $fechaItemIds));
        } else {
            $this->seleccionados[$grupoKey] = array_values(array_unique(array_merge($selected, $fechaItemIds)));
        }

        if (empty($this->seleccionados[$grupoKey])) {
            unset($this->seleccionados[$grupoKey]);
            $this->grupoActivo = null;
        } else {
            $this->grupoActivo = $grupoKey;
        }
    }

    public function crearPlanilla(string $grupoKey)
    {
        $this->validate([
            'fechaPlanilla' => 'required|date',
        ]);

        $grupo = $this->grupos[$grupoKey] ?? null;
        if (!$grupo) {
            return;
        }

        $itemIds = $this->seleccionados[$grupoKey] ?? [];
        if (empty($itemIds)) {
            return;
        }

        try {
            DB::beginTransaction();

            $fechaCarbon = \Carbon\Carbon::parse($this->fechaPlanilla);
            $prefijo = $fechaCarbon->format('d-m-Y');

            $ultimo = TesPlanillaEr::whereYear('fecha', $fechaCarbon->year)
                ->whereMonth('fecha', $fechaCarbon->month)
                ->where('numero', 'like', $prefijo . '-%')
                ->orderBy('id', 'desc')
                ->first();

            if ($ultimo) {
                $partes = explode('-', $ultimo->numero);
                $siguiente = (int) end($partes) + 1;
            } else {
                $siguiente = 1;
            }

            $numero = $prefijo . '-' . $siguiente;

            $planilla = TesPlanillaEr::create([
                'fecha' => $this->fechaPlanilla,
                'numero' => $numero,
                'tipo_id' => $grupo['tipo_id'],
                'dependencia_id' => $grupo['dependencia_id'],
                'turno' => $grupo['turno'],
                'er_numero' => null,
                'egresos_numero' => null,
                'transferencia_fecha' => null,
                'transferencia_confirmacion' => null,
            ]);

            TesCfeItem::whereIn('id', $itemIds)
                ->whereNull('planilla_er_id')
                ->update(['planilla_er_id' => $planilla->id]);

            DB::commit();

            $this->fecha = $planilla->fecha->format('Y-m-d');

            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Planilla creada',
                'text' => "Planilla {$numero} creada con " . count($itemIds) . " ítem(s).",
            ]);

            unset($this->seleccionados[$grupoKey]);
            if (empty($this->seleccionados)) {
                $this->grupoActivo = null;
            }
            $this->cargarGrupos();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Error al crear la planilla: ' . $e->getMessage(),
            ]);
        }
    }

    private function cargarGrupos(): void
    {
        $items = TesCfeItem::with([
                'cfe.cajaConcepto',
                'cfe.siifDistribucionTipo',
                'cfe.siifDistribucionDependencia',
                'siifDistribucion',
            ])
            ->whereNull('planilla_er_id')
            ->whereHas('cfe.cajaConcepto', fn($q) => $q->where('requiere_distribucion', true))
            ->whereHas('cfe', fn($q) => $q->whereNotNull('siif_distribucion_tipo_id'))
            ->orderBy('id')
            ->get();

        $this->grupos = [];
        foreach ($items as $item) {
            $cfe = $item->cfe;
            $tipoId = $cfe->siif_distribucion_tipo_id;
            $depId = $cfe->siif_distribucion_dependencia_id;
            $nocturno = mb_stripos($item->siifDistribucion?->concepto ?? '', 'nocturno') !== false;
            $turno = $nocturno ? 'Nocturno' : null;
            $key = $nocturno ? "{$tipoId}-{$depId}-Nocturno" : "{$tipoId}-{$depId}";

            if (!isset($this->grupos[$key])) {
                $this->grupos[$key] = [
                    'tipo_id' => $tipoId,
                    'tipo_nombre' => $cfe->siifDistribucionTipo->tipo ?? '—',
                    'dependencia_id' => $depId,
                    'dependencia_nombre' => $cfe->siifDistribucionDependencia->dependencia ?? '—',
                    'turno' => $turno,
                    'items' => [],
                ];
            }

            $this->grupos[$key]['items'][] = [
                'id' => $item->id,
                'detalle' => $item->detalle,
                'importe' => $item->importe,
                'fecha_cfe' => $item->cfe?->fecha?->format('d/m/Y'),
                'fecha_cfe_raw' => $item->cfe?->fecha?->format('Y-m-d'),
                'siif_distribucion_id' => $item->siif_distribucion_id,
                'cfe' => $item->cfe ? [
                    'documento_tipo' => $item->cfe->documento_tipo,
                    'documento_serie' => $item->cfe->documento_serie,
                    'documento_numero' => $item->cfe->documento_numero,
                ] : null,
                'siif_distribucion' => $item->siifDistribucion ? [
                    'concepto' => $item->siifDistribucion->concepto,
                ] : null,
            ];
        }

        $this->grupos = collect($this->grupos)->sortBy(function($grupo) {
            return collect($grupo['items'])->min('siif_distribucion_id') ?? 0;
        })->toArray();

        if (empty(array_keys($this->seleccionados))) {
            $this->grupoActivo = null;
        }
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

    public function borrarPlanilla(int $id): void
    {
        try {
            DB::beginTransaction();

            $planilla = TesPlanillaEr::findOrFail($id);

            TesCfeItem::where('planilla_er_id', $id)
                ->update(['planilla_er_id' => null]);

            $planilla->delete();

            DB::commit();

            $this->dispatchBrowserEvent('swal:success', [
                'title' => 'Planilla eliminada',
                'text' => "La planilla {$planilla->numero} ha sido eliminada.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Error al eliminar la planilla: ' . $e->getMessage(),
            ]);
        }
    }

    public function toggleConfirmada(int $id): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyPermission(['tesoreria.supervisar'])) {
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

    public function editarPlanilla(int $id): void
    {
        $this->planillaEditar = TesPlanillaEr::with(['tipo', 'dependencia'])->findOrFail($id);

        $this->edit_er_numero = $this->planillaEditar->er_numero;
        $this->edit_egresos_numero = $this->planillaEditar->egresos_numero;
        $this->edit_ingresos_numero = $this->planillaEditar->ingresos_numero;
        $this->edit_transferencia_fecha = $this->planillaEditar->transferencia_fecha?->format('Y-m-d');
        $this->edit_transferencia_confirmacion = $this->planillaEditar->transferencia_confirmacion;

        $this->mostrarModalEditar = true;
        $this->dispatchBrowserEvent('abrir-modal-editar');
    }

    public function guardarPlanilla(): void
    {
        $this->validate([
            'edit_er_numero' => 'nullable|string|max:255',
            'edit_egresos_numero' => 'nullable|string|max:255',
            'edit_ingresos_numero' => 'nullable|string|max:255',
            'edit_transferencia_fecha' => 'nullable|date',
            'edit_transferencia_confirmacion' => 'nullable|string|max:255',
        ]);

        $this->planillaEditar->update([
            'er_numero' => $this->edit_er_numero,
            'egresos_numero' => $this->edit_egresos_numero,
            'ingresos_numero' => $this->edit_ingresos_numero,
            'transferencia_fecha' => $this->edit_transferencia_fecha,
            'transferencia_confirmacion' => $this->edit_transferencia_confirmacion,
        ]);

        $this->dispatchBrowserEvent('swal:success', [
            'title' => 'Planilla actualizada',
            'text' => "Planilla {$this->planillaEditar->numero} actualizada correctamente.",
        ]);

        $this->dispatchBrowserEvent('cerrar-modal-editar');
        $this->cerrarModalEditar();
    }

    public function cerrarModalEditar(): void
    {
        $this->dispatchBrowserEvent('cerrar-modal-editar');
        $this->mostrarModalEditar = false;
        $this->planillaEditar = null;
        $this->edit_er_numero = null;
        $this->edit_egresos_numero = null;
        $this->edit_ingresos_numero = null;
        $this->edit_transferencia_fecha = null;
        $this->edit_transferencia_confirmacion = null;
    }

    public function render()
    {
        $query = TesPlanillaEr::with(['tipo', 'dependencia', 'items']);

        if ($this->fecha) {
            $query->where('fecha', $this->fecha);
        } else {
            $now = now();
            $currentYear = $now->year;
            $currentMonth = $now->month;
            $prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
            $prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;

            $query->where(function ($q) use ($currentYear, $currentMonth, $prevYear, $prevMonth) {
                $q->whereYear('fecha', $currentYear)->whereMonth('fecha', $currentMonth)
                  ->orWhere(function ($q2) use ($prevYear, $prevMonth) {
                      $q2->whereYear('fecha', $prevYear)->whereMonth('fecha', $prevMonth);
                  });
            });
        }

        $planillas = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')
            ->paginate(25);

        $planillasPorFecha = $planillas->groupBy(function ($p) {
            return $p->fecha->format('Y-m-d');
        });

        return view('livewire.tesoreria.estados-recaudacion.index', compact('planillas', 'planillasPorFecha'))
            ->extends('layouts.app')
            ->section('content');
    }
}
