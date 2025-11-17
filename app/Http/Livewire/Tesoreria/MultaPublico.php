<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa as MultaModel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use DOMDocument;
use DOMXPath;

class MultaPublico extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Propiedades de búsqueda y ordenamiento
    public $search = '';
    public $sortField = 'articulo';
    public $sortDirection = 'asc';
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'articulo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
        'perPage' => ['except' => 25],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
        Cache::flush();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
        Cache::flush();
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

    public function render()
    {
        $page = $this->page ?: 1;
        $cacheKey = 'multas_publico_search_' . $this->search . '_perpage_' . $this->perPage . '_sortfield_' . $this->sortField . '_sortdirection_' . $this->sortDirection . '_page_' . $page;

        $multas = Cache::remember($cacheKey, now()->addDay(), function () {
            $query = MultaModel::query()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->whereRaw("CONCAT_WS('.', articulo, apartado) like ?", ["%" . $this->search . "%"])
                            ->orWhere('descripcion', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy($this->sortField, $this->sortDirection);

            if ((int)$this->perPage === -1) {
                return $query->get();
            } else {
                return $query->paginate($this->perPage);
            }
        });

        return view('livewire.tesoreria.multa-publico', compact('multas'));
    }
}
