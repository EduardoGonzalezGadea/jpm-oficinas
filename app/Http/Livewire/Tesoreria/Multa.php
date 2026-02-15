<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa as MultaModel;
use App\Builders\MultaQueryBuilder;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\CacheMultaTrait;

class Multa extends Component
{
    use WithPagination, CacheMultaTrait;

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
    public $perPage = 50;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'articulo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['except' => 50],
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
        // Carga inmediata sin flags innecesarios
    }

    public function updatedSearch($value)
    {
        $this->resetPage();
        // Invalidar solo caché de multas, no toda la caché
        $this->invalidateMultasCache();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
        $this->invalidateMultasCache();
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
        $this->invalidateMultasCache();
    }

    public function refreshList()
    {
        $this->invalidateMultasCache();
        $this->resetPage();
    }

    public function actualizarSoa()
    {
        try {
            $controller = new \App\Http\Controllers\UtilidadController();
            $response = $controller->actualizarValoresSoa();
            $data = $response->getData();

            if (isset($data->success) && $data->success) {
                $this->dispatchBrowserEvent('swal:success', ['text' => $data->message]);
                $this->invalidateMultasCache();
                $this->resetPage();
            } else {
                $mensaje = $data->error ?? 'Error desconocido al actualizar SOA.';
                $this->dispatchBrowserEvent('swal:error', ['text' => $mensaje]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal:error', ['text' => 'Error: ' . $e->getMessage()]);
        }
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
                Log::info('Multa store operation started', [
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

                Log::info('Multa store operation completed', ['multa_id' => $multa->id]);

                // Invalidar solo caché de multas
                $this->invalidateMultasCache();
            } catch (\Exception $e) {
                Log::error('Multa store operation failed', [
                    'error' => $e->getMessage(),
                    'multa_id' => $this->multa_id,
                    'articulo' => $this->articulo
                ]);

                throw $e;
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
            $this->invalidateMultasCache();
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
        $cacheKey = $this->getMultasCacheKey();
        $ttl = $this->getMultasCacheTTL();

        $multas = Cache::remember($cacheKey, $ttl, function () {
            // Usar Query Builder optimizado
            $query = MultaQueryBuilder::forList([
                'search' => $this->search,
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
            ]);

            // Usar simplePaginate para mejor rendimiento (no cuenta total de registros)
            if ((int)$this->perPage === -1) {
                return $query->get();
            } else {
                return $query->simplePaginate($this->perPage);
            }
        });

        return view('livewire.tesoreria.multa', compact('multas'));
    }
}
