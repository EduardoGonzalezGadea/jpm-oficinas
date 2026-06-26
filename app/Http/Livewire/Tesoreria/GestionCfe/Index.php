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
use App\Services\Tesoreria\CfeCreatorService;

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

            $this->dispatchBrowserEvent('swal:modal', [
                'type' => 'success',
                'title' => 'CFE eliminado',
                'text' => 'El CFE ha sido eliminado correctamente.',
            ]);
        } catch (\RuntimeException $e) {
            $this->dispatchBrowserEvent('swal:toast-error', [
                'text' => $e->getMessage(),
            ]);
        }
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $from = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù', 'â', 'ê', 'î', 'ô', 'û'];
        $to = ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'];
        return str_replace($from, $to, $texto);
    }

    public function render()
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

        $cajaConceptos = CajaConcepto::whereNull('deleted_at')
            ->ordenado()
            ->get();

        $siifDependencias = SiifDistribucionDependencia::whereNull('deleted_at')
            ->orderBy('dependencia')
            ->get();

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

        $mediosDePago = MedioDePago::activos()->ordenado()->get();

        $currentYear = (int) date('Y');
        $anosRegistrados = TesCfe::whereNotNull('fecha')
            ->selectRaw('YEAR(fecha) as ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->map(fn($year) => (int) $year)
            ->toArray();

        if (!in_array($currentYear, $anosRegistrados)) {
            array_unshift($anosRegistrados, $currentYear);
        }

        return view('livewire.tesoreria.gestion-cfe.index', compact('cfes', 'cajaConceptos', 'siifDependencias', 'distribuciones', 'editDistribuciones', 'nuevoDistribuciones', 'mediosDePago', 'anosRegistrados'))
            ->extends('layouts.app')
            ->section('content');
    }
}
