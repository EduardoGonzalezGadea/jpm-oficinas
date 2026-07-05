<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa303 as Multa303Model;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use App\Traits\CacheMultaTrait;

class Multa303Publico extends Component
{
    use WithPagination, CacheMultaTrait;

    protected $paginationTheme = 'bootstrap';

    // Propiedades de búsqueda y ordenamiento
    public $search = '';
    public $sortField = 'codigo';
    public $sortDirection = 'asc';
    public $perPage = 50;

    private array $allowedSortFields = ['codigo', 'grupo', 'descripcion'];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'codigo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['except' => 50],
    ];

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

    public function render()
    {
        $cacheKey = $this->getMultasCacheKey([
            'prefix' => 'multas303_publico',
            'search' => $this->search,
            'perPage' => $this->perPage,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->page
        ]);
        $ttl = $this->getMultasCacheTTL();

        $multas = Cache::remember($cacheKey, $ttl, function () {
            $query = Multa303Model::query();

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

        $grupos = Cache::remember('multas303_publico.grupos', now()->addDays(1), function () {
            return Multa303Model::select('grupo')->distinct()->orderBy('grupo')->pluck('grupo');
        });

        return view('livewire.tesoreria.multa303-publico', compact('multas', 'grupos'));
    }
}