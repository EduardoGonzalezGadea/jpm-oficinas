<?php

namespace App\Livewire\Tesoreria\GestionCfe;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\CajaConcepto;
use App\Models\Tesoreria\SiifDistribucion;
use App\Models\Tesoreria\SiifDistribucionDependencia;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\EventualInstitucion;
use App\Exceptions\Tesoreria\CfeNotFoundException;
use App\Exceptions\Tesoreria\CfeValidationException;
use App\Helpers\TextoHelper;
use App\Services\Tesoreria\CfeCreatorService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination, WithFileUploads;
    use WithConfirmacionCarga;
    use WithEdicionCfe;
    use WithNuevoCfe;


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
    public $totalesPorInstitucion = [];

    public array $filtroMeses = [];
    public $filtroAno = null;

    // Planillas comunes
    public array $cfesSeleccionados = [];
    public $fechaPlanillaComun;
    public $mostrarSelectorPlanillas = false;

    public function mount(): void
    {
        $this->filtroAno = (int) date('Y');
        $this->fechaPlanillaComun = date('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroConcepto(): void
    {
        $this->cfesSeleccionados = [];
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

    public function limpiarFiltros(): void
    {
        $this->search = '';
        $this->filtroConcepto = null;
        $this->filtroMeses = [];
        $this->filtroAno = (int) date('Y');
        $this->cfesSeleccionados = [];
        $this->resetPage();
    }

    #[On('borrarCfe')]
    public function borrarCfe($cfeId = null, $id = null): void
    {
        $targetId = (int) ($cfeId ?? $id);
        if (!$targetId) return;

        try {
            $this->cfeCreator->deleteCfe($targetId);

            $this->dispatch('swal:toast-success', text: 'CFE eliminado correctamente.');
        } catch (CfeNotFoundException | CfeValidationException | \RuntimeException $e) {
            $this->dispatch('swal:toast-error', text: $e->getMessage());
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast-error', text: 'Error al eliminar el CFE: ' . $e->getMessage());
        }
    }

    public function toggleConfirmado($cfeId)
    {
        if (auth()->user()->cannot('tesoreria.supervisar')) {
            abort(403);
        }

        $cfe = TesCfe::with(['cajaConcepto', 'items'])->findOrFail($cfeId);

        if (!$cfe->cajaConcepto || !$cfe->cajaConcepto->requiere_confirmacion) {
            abort(403);
        }

        if ($cfe->items->isEmpty()) {
            return;
        }

        $todosConfirmados = $cfe->items->every(fn($i) => $i->confirmado);
        $nuevoEstado = !$todosConfirmados;

        TesCfeItem::where('tes_cfe_id', $cfeId)->update(['confirmado' => $nuevoEstado]);

        // Si el concepto filtrado requiere confirmación y permite planilla, actualizar selección
        if ($this->filtroConcepto && $this->mostrarSelectorPlanillas && $cfe->cajaConcepto->requiere_confirmacion) {
            if ($nuevoEstado) {
                // Si se confirmó y no está en la lista, agregarlo
                if (!in_array($cfeId, $this->cfesSeleccionados) && !$cfe->planilla_comun_id) {
                    $this->cfesSeleccionados[] = $cfeId;
                }
            } else {
                // Si se desconfirmó, quitarlo de la lista
                $idx = array_search($cfeId, $this->cfesSeleccionados);
                if ($idx !== false) {
                    unset($this->cfesSeleccionados[$idx]);
                    $this->cfesSeleccionados = array_values($this->cfesSeleccionados);
                }
            }
        }

        $this->dispatch('swal:toast-success', text: 'Estado de confirmación actualizado.');
    }

    public function toggleCfeSeleccionado($cfeId)
    {
        $idx = array_search($cfeId, $this->cfesSeleccionados);
        if ($idx !== false) {
            unset($this->cfesSeleccionados[$idx]);
            $this->cfesSeleccionados = array_values($this->cfesSeleccionados);
        } else {
            $this->cfesSeleccionados[] = $cfeId;
        }
    }

    public function crearPlanillaComun()
    {
        $this->validate([
            'fechaPlanillaComun' => 'required|date',
            'filtroConcepto' => 'required',
        ]);

        if (empty($this->cfesSeleccionados)) {
            $this->dispatch('swal:toast-error', text: 'Debe seleccionar al menos un CFE.');
            return;
        }

        try {
            \DB::beginTransaction();

            $concepto = CajaConcepto::findOrFail($this->filtroConcepto);
            $fechaCarbon = \Carbon\Carbon::parse($this->fechaPlanillaComun);
            
            // Generar número de planilla: concepto_id-YYYY-MM-DD-numero_incremental
            $prefijo = $concepto->id . '-' . $fechaCarbon->format('Y-m-d');
            
            $ultimo = \App\Models\Tesoreria\TesPlanillaComun::where('numero', 'like', $prefijo . '-%')
                ->orderBy('id', 'desc')
                ->first();

            if ($ultimo) {
                $partes = explode('-', $ultimo->numero);
                $siguiente = (int) end($partes) + 1;
            } else {
                $siguiente = 1;
            }

            $numero = $prefijo . '-' . str_pad($siguiente, 2, '0', STR_PAD_LEFT);

            $planilla = \App\Models\Tesoreria\TesPlanillaComun::create([
                'fecha' => $this->fechaPlanillaComun,
                'numero' => $numero,
                'tes_caja_concepto_id' => $this->filtroConcepto,
            ]);

            TesCfe::whereIn('id', $this->cfesSeleccionados)
                ->whereNull('planilla_comun_id')
                ->update(['planilla_comun_id' => $planilla->id]);

            \DB::commit();

            $this->dispatch('swal:success', title: 'Planilla creada', text: "Planilla {$numero} creada con " . count($this->cfesSeleccionados) . " CFE(s).");

            $this->cfesSeleccionados = [];
            $this->fechaPlanillaComun = date('Y-m-d');

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('swal:toast-error', text: 'Error al crear la planilla: ' . $e->getMessage());
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

            $this->dispatch('swal:toast-error', text: 'Error al cargar la lista de CFEs. Recargue la página e intente nuevamente.');

            $cfes = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
            $cajaConceptos = collect();
            $siifDependencias = collect();
            $distribuciones = [];
            $editDistribuciones = [];
            $nuevoDistribuciones = [];
            $mediosDePago = collect();
            $instituciones = collect();
            $anosRegistrados = [(int) date('Y')];
            $mostrarColumnaConf = false;
            $mostrarTotalesInstitucion = false;
            $conceptoPermitePlanilla = null;

            return view('livewire.tesoreria.gestion-cfe.index', compact(
                'cfes', 'cajaConceptos', 'siifDependencias', 'distribuciones',
                'editDistribuciones', 'nuevoDistribuciones', 'mediosDePago', 'instituciones', 'anosRegistrados',
                'mostrarColumnaConf', 'mostrarTotalesInstitucion', 'conceptoPermitePlanilla'
            ));
        }
    }

    private function doRender()
    {
        $cfes = TesCfe::with(['items.planillaEr', 'mediosPago', 'cajaConcepto.siifDistribucionTipo', 'cajaConcepto', 'siifDistribucionDependencia', 'planillaComun'])
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

        $mostrarColumnaConf = false;
        if ($this->filtroConcepto) {
            $conceptoFiltrado = CajaConcepto::find($this->filtroConcepto);
            $mostrarColumnaConf = $conceptoFiltrado && (bool) $conceptoFiltrado->requiere_confirmacion;
        }

        // Determinar si mostrar selectores de planillas comunes
        $this->mostrarSelectorPlanillas = false;
        $conceptoPermitePlanilla = null;
        if ($this->filtroConcepto) {
            $conceptoFiltrado = CajaConcepto::find($this->filtroConcepto);
            if ($conceptoFiltrado && 
                !$conceptoFiltrado->requiere_distribucion && 
                $conceptoFiltrado->permite_planilla) {
                $this->mostrarSelectorPlanillas = true;
                $conceptoPermitePlanilla = $conceptoFiltrado;
                
                // Si requiere confirmación, pre-seleccionar solo los CFEs confirmados sin planilla
                if ($conceptoFiltrado->requiere_confirmacion) {
                    // Obtener todos los IDs de CFEs confirmados sin planilla (sin paginar)
                    $queryAllCfes = TesCfe::where('tes_caja_concepto_id', $this->filtroConcepto)
                        ->whereNull('planilla_comun_id')
                        ->whereHas('items', function($q) {
                            // Solo CFEs donde TODOS los items están confirmados
                            $q->whereColumn('tes_cfe_items.tes_cfe_id', 'tes_cfes.id')
                              ->where('confirmado', true);
                        })
                        ->whereNotExists(function($q) {
                            // Excluir CFEs que tengan algún item NO confirmado
                            $q->select(\DB::raw(1))
                              ->from('tes_cfe_items')
                              ->whereColumn('tes_cfe_items.tes_cfe_id', 'tes_cfes.id')
                              ->where('confirmado', false);
                        })
                        ->where(function ($query) {
                            $query->where('emisor_nombre', 'like', '%' . $this->search . '%')
                                ->orWhere('documento_numero', 'like', '%' . $this->search . '%')
                                ->orWhere('receptor_documento_ruc', 'like', '%' . $this->search . '%')
                                ->orWhere('receptor_nombre_denominacion', 'like', '%' . $this->search . '%');
                        });
                    
                    if ($this->filtroAno) {
                        $queryAllCfes->whereYear('fecha', $this->filtroAno);
                    }
                    
                    if (!empty($this->filtroMeses)) {
                        $queryAllCfes->where(function ($query) {
                            foreach ($this->filtroMeses as $mes) {
                                $query->orWhereMonth('fecha', (int) $mes);
                            }
                        });
                    }
                    
                    $this->cfesSeleccionados = $queryAllCfes->pluck('id')->toArray();
                }
            }
        }

        // Calcular totales por institución si el concepto filtrado requiere institución
        $mostrarTotalesInstitucion = false;
        $this->totalesPorInstitucion = [];
        
        if ($this->filtroConcepto) {
            $conceptoFiltrado = CajaConcepto::find($this->filtroConcepto);
            if ($conceptoFiltrado && $conceptoFiltrado->requiere_institucion) {
                $mostrarTotalesInstitucion = true;
                
                $queryTotales = TesCfe::where('tes_caja_concepto_id', $this->filtroConcepto)
                    ->where(function ($query) {
                        $query->where('emisor_nombre', 'like', '%' . $this->search . '%')
                            ->orWhere('documento_numero', 'like', '%' . $this->search . '%')
                            ->orWhere('receptor_documento_ruc', 'like', '%' . $this->search . '%')
                            ->orWhere('receptor_nombre_denominacion', 'like', '%' . $this->search . '%')
                            ->orWhereHas('items.planillaEr', fn($q) => $q->where('numero', 'like', '%' . $this->search . '%'));
                    });

                if ($this->filtroAno) {
                    $queryTotales->whereYear('fecha', $this->filtroAno);
                }

                if (!empty($this->filtroMeses)) {
                    $queryTotales->where(function ($query) {
                        foreach ($this->filtroMeses as $mes) {
                            $query->orWhereMonth('fecha', (int) $mes);
                        }
                    });
                }

                $this->totalesPorInstitucion = $queryTotales
                    ->select('institucion_id', \DB::raw('SUM(total_a_pagar) as total_monto'))
                    ->whereNotNull('institucion_id')
                    ->groupBy('institucion_id')
                    ->with('institucion')
                    ->get();
            }
        }

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
                    ->orderBy('concepto', 'asc')
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
                    ->orderBy('concepto', 'asc')
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
                    ->orderBy('concepto', 'asc')
                    ->get()
                    ->unique(function ($item) {
                        return $item->tipo_id . '-' . $item->dependencia_id . '-' . $item->concepto;
                    });
            }
        }

        $mediosDePago = Cache::remember('cfe_medios_pago', 300, fn() =>
            MedioDePago::activos()->ordenado()->get()
        );

        $instituciones = Cache::remember('cfe_instituciones', 300, fn() =>
            EventualInstitucion::where('activa', true)->orderBy('nombre')->get()
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

        return view('livewire.tesoreria.gestion-cfe.index', compact('cfes', 'cajaConceptos', 'siifDependencias', 'distribuciones', 'editDistribuciones', 'nuevoDistribuciones', 'mediosDePago', 'instituciones', 'anosRegistrados', 'mostrarColumnaConf', 'mostrarTotalesInstitucion', 'conceptoPermitePlanilla'));
    }
}
