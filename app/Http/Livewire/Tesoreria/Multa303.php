<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa303 as Multa303Model;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\CacheMultaTrait;

class Multa303 extends Component
{
    use WithPagination, CacheMultaTrait;

    protected $paginationTheme = 'bootstrap';

    // Propiedades del formulario
    public $multa_id;
    public $grupo;
    public $codigo;
    public $descripcion;
    public $valor_ur;

    // Propiedades de control
    public $isOpen = false;
    public $isEdit = false;
    public $search = '';
    public $sortField = 'codigo';
    public $sortDirection = 'asc';
    public $perPage = 50;

    private array $allowedSortFields = ['codigo', 'grupo', 'descripcion', 'valor_ur'];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'codigo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['except' => 50],
    ];

    protected $rules = [
        'grupo' => 'required|string|max:255',
        'codigo' => 'required|string|max:50',
        'descripcion' => 'required|string',
        'valor_ur' => 'required|string|max:100',
    ];

    protected $messages = [
        'grupo.required' => 'El grupo es obligatorio.',
        'codigo.required' => 'El código es obligatorio.',
        'descripcion.required' => 'La descripción es obligatoria.',
        'valor_ur.required' => 'El valor en UR es obligatorio.',
    ];

    public function mount()
    {
        // Carga sin flags innecesarios
    }

    /**
     * Normalización de la búsqueda: convierte comas en puntos
     */
    public function updatedSearch($value)
    {
        $this->search = str_replace(',', '.', $value);
        $this->resetPage();
        $this->invalidateMultasCache();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
        $this->invalidateMultasCache();
    }

    public function sortBy($field)
    {
        if (!in_array($field, $this->allowedSortFields, true)) {
            return;
        }
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

    public function create()
    {
        $this->resetInputFields();
        $this->isEdit = false;
        $this->openModal();
    }

    public function edit($id)
    {
        $multa = Multa303Model::findOrFail($id);
        $this->multa_id = $multa->id;
        $this->grupo = $multa->grupo;
        $this->codigo = $multa->codigo;
        $this->descripcion = $multa->descripcion;
        $this->valor_ur = $multa->valor_ur;

        $this->isEdit = true;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        DB::transaction(function () {
            try {
                Log::info('Multa303 store operation started', [
                    'multa_id' => $this->multa_id,
                    'codigo' => $this->codigo,
                    'is_edit' => $this->isEdit
                ]);

                $multa = Multa303Model::updateOrCreate(
                    ['id' => $this->multa_id],
                    [
                        'grupo' => trim($this->grupo),
                        'codigo' => trim($this->codigo),
                        'descripcion' => trim($this->descripcion),
                        'valor_ur' => trim($this->valor_ur),
                    ]
                );

                Log::info('Multa303 store operation completed', ['multa_id' => $multa->id]);

                // Invalidar caché de multas
                $this->invalidateMultasCache();
            } catch (\Exception $e) {
                Log::error('Multa303 store operation failed', [
                    'error' => $e->getMessage(),
                    'multa_id' => $this->multa_id,
                    'codigo' => $this->codigo
                ]);

                throw $e;
            }
        });

        $this->closeModal();
        $this->resetInputFields();

        $this->dispatchBrowserEvent('swal:success', [
            'text' => $this->isEdit ? 'Multa (Dec. 303/2023) actualizada exitosamente.' : 'Multa (Dec. 303/2023) creada exitosamente.'
        ]);
    }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            Multa303Model::findOrFail($id)->delete();
            $this->invalidateMultasCache();
        });
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Multa (Dec. 303/2023) eliminada exitosamente.']);
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
        $this->grupo = '';
        $this->codigo = '';
        $this->descripcion = '';
        $this->valor_ur = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $cacheKey = $this->getMultasCacheKey([
            'prefix' => 'multas303',
            'search' => $this->search,
            'perPage' => $this->perPage,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->page
        ]);
        $ttl = $this->getMultasCacheTTL();

        $multas = Cache::remember($cacheKey, $ttl, function () {
            // Construir la consulta
            $query = Multa303Model::query();

            // Filtrar si hay búsqueda, excluyendo valor_ur según la especificación
            if (!empty($this->search)) {
                $query->where(function ($q) {
                    $q->where('codigo', 'like', $this->search . '%')
                      ->orWhere('grupo', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
                });
            }

            $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';
            $field = in_array($this->sortField, $this->allowedSortFields, true) ? $this->sortField : 'codigo';

            // Ordenamiento numérico para el campo codigo (separado por puntos)
            if ($field === 'codigo') {
                $query->orderByRaw("CAST(SUBSTRING_INDEX(codigo, '.', 1) AS UNSIGNED) {$dir}")
                      ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, '.', 2), '.', -1) AS UNSIGNED) {$dir}")
                      ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, '.', 3), '.', -1) AS UNSIGNED) {$dir}")
                      ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo, '.', 4), '.', -1) AS UNSIGNED) {$dir}")
                      ->orderBy('codigo', $dir);
            } else {
                $query->orderBy($field, $dir);
            }

            if ((int)$this->perPage === -1) {
                return $query->get();
            } else {
                return $query->paginate($this->perPage);
            }
        });

        // Obtener todos los grupos únicos para autocompletado u organización en UI si fuera necesario
        $grupos = Cache::remember('multas303.grupos', now()->addDays(1), function () {
            return Multa303Model::select('grupo')->distinct()->orderBy('grupo')->pluck('grupo');
        });

        return view('livewire.tesoreria.multa303', compact('multas', 'grupos'));
    }
}
