<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa as MultaModel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
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
    ];

    public function updatingSearch()
    {
        $this->resetPage();
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
    }

    public function render()
    {
        $query = MultaModel::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereRaw("CONCAT_WS('.', articulo, apartado) like ?", ["%" . $this->search . "%"])
                        ->orWhere('descripcion', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        if ((int)$this->perPage === -1) {
            $multas = $query->get();
        } else {
            $multas = $query->paginate($this->perPage);
        }

        return view('livewire.tesoreria.multa-publico', compact('multas'));
    }
}
