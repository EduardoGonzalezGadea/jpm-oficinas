<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa as MultaModel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Multa extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // --- REFACTORIZADO ---
    // Propiedades del formulario
    public $multa_id;
    public $articulo;
    public $apartado;
    public $descripcion;
    public $moneda = 'UR';
    public $importe_original;
    public $importe_unificado;
    public $decreto;

    // Propiedades de control
    public $isOpen = false;
    public $isEdit = false;
    public $search = '';
    public $sortField = 'articulo';
    public $sortDirection = 'asc';
    public $perPage = 25;
    public $multasCargadas = false; // Flag para controlar carga progresiva

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'articulo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['except' => 25],
    ];

    protected $rules = [
        'articulo' => 'required|numeric',
        'apartado' => 'nullable|string|max:10',
        'descripcion' => 'required|string',
        'moneda' => 'required|string|max:3',
        'importe_original' => 'required|numeric|min:0',
        'importe_unificado' => 'nullable|numeric|min:0',
        'decreto' => 'nullable|string|max:100',
    ];

    protected $messages = [
        'articulo.required' => 'El artículo es obligatorio.',
        'descripcion.required' => 'La descripción es obligatoria.',
        'importe_original.required' => 'El importe original es obligatorio.',
        'importe_original.numeric' => 'El importe original debe ser un número.',
    ];

    public function mount()
    {
        // Iniciar carga automática de multas después de mount
        $this->loadMultasAutomaticamente();
    }



    public function updatingSearch()
    {
        $this->resetPage();
        $this->multasCargadas = false; // Reset flag para recargar datos
        Cache::flush();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function loadMultas()
    {
        $this->multasCargadas = true;
    }



    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
        $this->resetPage();
        Cache::flush();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isEdit = false;
        $this->openModal();
    }

    public function edit($id)
    {
        $multa = MultaModel::findOrFail($id);
        $this->multa_id = $multa->id;
        $this->articulo = $multa->articulo;
        $this->apartado = $multa->apartado;
        $this->descripcion = $multa->descripcion;
        $this->moneda = $multa->moneda;
        $this->importe_original = $multa->importe_original;
        $this->importe_unificado = $multa->importe_unificado;
        $this->decreto = $multa->decreto;

        $this->isEdit = true;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        DB::transaction(function () {
            try {
                // Log the operation for debugging
                \Log::info('Multa store operation started', [
                    'multa_id' => $this->multa_id,
                    'articulo' => $this->articulo,
                    'is_edit' => $this->isEdit
                ]);

                $multa = MultaModel::updateOrCreate(
                    ['id' => $this->multa_id],
                    [
                        'articulo' => $this->articulo,
                        'apartado' => $this->apartado,
                        'descripcion' => $this->descripcion,
                        'moneda' => $this->moneda,
                        'importe_original' => $this->importe_original,
                        'importe_unificado' => $this->importe_unificado,
                        'decreto' => $this->decreto
                    ]
                );

                \Log::info('Multa store operation completed', ['multa_id' => $multa->id]);
                Cache::flush();
            } catch (\Exception $e) {
                \Log::error('Multa store operation failed', [
                    'error' => $e->getMessage(),
                    'multa_id' => $this->multa_id,
                    'articulo' => $this->articulo
                ]);

                throw $e; // Re-throw to trigger transaction rollback
            }
        });

        $this->closeModal();
        $this->resetInputFields();

        $this->dispatchBrowserEvent('swal:success', ['text' => $this->isEdit ? 'Multa actualizada exitosamente.' : 'Multa creada exitosamente.']);
    }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            MultaModel::find($id)->delete();
            Cache::flush();
        });
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Multa eliminada exitosamente.']);
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields()
    {
        $this->multa_id = null;
        $this->articulo = '';
        $this->apartado = '';
        $this->descripcion = '';
        $this->moneda = 'UR';
        $this->importe_original = '';
        $this->importe_unificado = '';
        $this->decreto = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $multas = collect();

        if (!empty($this->search) || $this->multasCargadas) {
            $page = $this->page ?: 1;
            $cacheKey = 'multas_search_' . $this->search . '_perpage_' . $this->perPage . '_sortfield_' . $this->sortField . '_sortdirection_' . $this->sortDirection . '_page_' . $page;

            $multas = Cache::remember($cacheKey, now()->addDay(), function () {
                $query = MultaModel::query();

                if (!empty($this->search)) {
                    $query->where(function ($q) {
                        $q->whereRaw("CONCAT_WS('.', articulo, apartado) like ?", ["%" . $this->search . "%"])
                            ->orWhere('descripcion', 'like', '%' . $this->search . '%');
                    });
                }

                $query->orderBy($this->sortField, $this->sortDirection);

                if ((int)$this->perPage === -1) {
                    return $query->get();
                } else {
                    return $query->paginate($this->perPage);
                }
            });
        }

        return view('livewire.tesoreria.multa', compact('multas'));
    }

    public function loadMultasAutomaticamente()
    {
        $this->multasCargadas = true;
    }


}
