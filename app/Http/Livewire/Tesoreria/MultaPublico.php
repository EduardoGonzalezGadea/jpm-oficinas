<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa as MultaModel;
use App\Builders\MultaQueryBuilder;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Traits\CacheMultaTrait;
use DOMDocument;
use DOMXPath;

class MultaPublico extends Component
{
    use WithPagination, CacheMultaTrait;

    protected $paginationTheme = 'bootstrap';

    // Propiedades de búsqueda y ordenamiento
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

    public function updatingSearch()
    {
        $this->resetPage();
        // Invalidar solo caché de multas
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

    public function render()
    {
        $cacheKey = 'multas_publico.' . $this->getMultasCacheKey();
        $ttl = $this->getMultasCacheTTL();

        $multas = Cache::remember($cacheKey, $ttl, function () {
            // Usar Query Builder optimizado
            $query = MultaQueryBuilder::forList([
                'search' => $this->search,
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
            ]);

            if ((int)$this->perPage === -1) {
                return $query->get();
            } else {
                return $query->paginate($this->perPage);
            }
        });

        return view('livewire.tesoreria.multa-publico', compact('multas'));
    }
}
