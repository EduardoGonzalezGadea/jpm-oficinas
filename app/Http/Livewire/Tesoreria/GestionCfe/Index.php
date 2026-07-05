<?php

namespace App\Http\Livewire\Tesoreria\GestionCfe;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\MedioDePago;
use App\Exceptions\Tesoreria\CfeNotFoundException;
use App\Exceptions\Tesoreria\CfeValidationException;
use App\Helpers\TextoHelper;
use App\Services\Tesoreria\CfeCreatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    use WithConfirmacionCarga;
    use WithEdicionCfe;
    use WithNuevoCfe;

    protected $listeners = ['borrarCfe'];

    protected CfeCreatorService $cfeCreator;

    public function boot(CfeCreatorService $cfeCreator): void
    {
        $this->cfeCreator = $cfeCreator;
    }

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $archivoPdf;

    public $cajaConceptoSeleccionado = null;
    public $siifDependenciaSeleccionado = 1;
    public array $itemDistribuciones = [];
    public $filtroConcepto = null;
    public array $filtroMeses = [];
    public $filtroAno = null;

    public function mount(): void
    {
        $this->filtroAno = (int) date('Y');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroConcepto(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroMeses(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroAno(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltroMeses(): void
    {
        $this->filtroMeses = [];
        $this->resetPage();
    }

    public function borrarCfe(int $cfeId): void
    {
        try {
            $this->cfeCreator->deleteCfe($cfeId);

            $this->dispatchBrowserEvent('swal:toast-success', [
                'text' => 'CFE eliminado correctamente.',
            ]);
        } catch (CfeNotFoundException | CfeValidationException $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => $e->getMessage(),
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        try {
            return $this->doRender();
        } catch (\Throwable $e) {
            Log::error('Error en render de GestionCfe', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => 'Error al cargar la lista de CFEs. Recargue la página e intente nuevamente.',
            ]);

            $cfes = collect();
            $cajaConceptos = collect();
            $siifDependencias = collect();
            $distribuciones = [];
            $editDistribuciones = [];
            $nuevoDistribuciones = [];
            $mediosDePago = collect();
            $anosRegistrados = [(int) date('Y')];

            return view('livewire.tesoreria.gestion-cfe.index', compact(
                'cfes', 'cajaConceptos', 'siifDependencias', 'distribuciones',
                'editDistribuciones', 'nuevoDistribuciones', 'mediosDePago', 'anosRegistrados'
            ))->extends('layouts.app')->section('content');
        }
    }

    private function doRender()
    {
        $cfes = TesCfe::with(['items.planillaEr', 'mediosPago', 'cajaConcepto', 'siifDistribucionTipo', 'siifDistribucionDependencia'])
            ->withCount(['items as items_en_planilla_count' => fn($q) => $q->whereNotNull('planilla_er_id')])
            ->where(function ($query) {
                $query->where('emisor_nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('documento_numero', 'like', '%' . $this->search . '%')
                    ->orWhere('receptor_documento_ruc', 'like', '%' . $this->search . '%')
                    ->orWhere('receptor_nombre_denominacion', 'like', '%' . $this->search . '%')
                    ->orWhereHas('items.planillaEr', fn($q) => $q->where('numero', 'like', '%' . $this->search . '%'));
            });

        if ($this->filtroConcepto) {
            $cfes->where('tes_caja_concepto_id', $this->filtroConcepto);
        }

        if ($this->filtroAno) {
            $cfes->whereYear('fecha', $this->filtroAno);
        }

        if (!empty($this->filtroMeses)) {
            $cfes->where(function ($query) {
                foreach ($this->filtroMeses as $mes) {
                    $query->orWhereMonth('fecha', (int) $mes);
                }
            });
        }

        $cfes = $cfes->orderBy('fecha', 'desc')->orderBy('documento_numero', 'desc')
            ->paginate(15);

        $cajaConceptos = Cache::remember('cfe_caja_conceptos', 300, fn() =>
            CajaConcepto::whereNull('deleted_at')->ordenado()->get()
        );

        $siifDependencias = Cache::remember('cfe_siif_dependencias', 300, fn() =>
            SiifDistribucionDependencia::whereNull('deleted_at')->orderBy('dependencia')->get()
        );

        $distribuciones = [];
        if ($this->cajaConceptoSeleccionado && $this->siifDependenciaSeleccionado) {
            $cajaConcepto = CajaConcepto::find($this->cajaConceptoSeleccionado);
            if ($cajaConcepto && $cajaConcepto->siif_distribucion_tipo_id) {
                $distribuciones = SiifDistribucion::where('tipo_id', $cajaConcepto->siif_distribucion_tipo_id)
                    ->where('dependencia_id', $this->siifDependenciaSeleccionado)
                    ->whereNull('deleted_at')
                    ->get()
                    ->unique(function ($item) {
                        return $item->tipo_id . '-' . $item->dependencia_id . '-' . $item->concepto;
                    });
            }
        }

        $editDistribuciones = [];
        if ($this->editCajaConceptoSeleccionado && $this->editSiifDependenciaSeleccionado) {
            $editCajaConcepto = CajaConcepto::find($this->editCajaConceptoSeleccionado);
            if ($editCajaConcepto && $editCajaConcepto->siif_distribucion_tipo_id) {
                $editDistribuciones = SiifDistribucion::where('tipo_id', $editCajaConcepto->siif_distribucion_tipo_id)
                    ->where('dependencia_id', $this->editSiifDependenciaSeleccionado)
                    ->whereNull('deleted_at')
                    ->get()
                    ->unique(function ($item) {
                        return $item->tipo_id . '-' . $item->dependencia_id . '-' . $item->concepto;
                    });
            }
        }

        $nuevoDistribuciones = [];
        if ($this->nuevoCajaConceptoSeleccionado && $this->nuevoSiifDependenciaSeleccionado) {
            $nuevoCajaConcepto = CajaConcepto::find($this->nuevoCajaConceptoSeleccionado);
            if ($nuevoCajaConcepto && $nuevoCajaConcepto->siif_distribucion_tipo_id) {
                $nuevoDistribuciones = SiifDistribucion::where('tipo_id', $nuevoCajaConcepto->siif_distribucion_tipo_id)
                    ->where('dependencia_id', $this->nuevoSiifDependenciaSeleccionado)
                    ->whereNull('deleted_at')
                    ->get()
                    ->unique(function ($item) {
                        return $item->tipo_id . '-' . $item->dependencia_id . '-' . $item->concepto;
                    });
            }
        }

        $mediosDePago = Cache::remember('cfe_medios_pago', 300, fn() =>
            MedioDePago::activos()->ordenado()->get()
        );

        $currentYear = (int) date('Y');
        $anosRegistrados = Cache::remember('cfe_anos_registrados', 300, function () use ($currentYear) {
            $anos = TesCfe::whereNotNull('fecha')
                ->selectRaw('YEAR(fecha) as ano')
                ->distinct()
                ->orderBy('ano', 'desc')
                ->pluck('ano')
                ->map(fn($year) => (int) $year)
                ->toArray();

            if (!in_array($currentYear, $anos)) {
                array_unshift($anos, $currentYear);
            }

            return $anos;
        });

        return view('livewire.tesoreria.gestion-cfe.index', compact('cfes', 'cajaConceptos', 'siifDependencias', 'distribuciones', 'editDistribuciones', 'nuevoDistribuciones', 'mediosDePago', 'anosRegistrados'))
            ->extends('layouts.app')
            ->section('content');
    }
}
