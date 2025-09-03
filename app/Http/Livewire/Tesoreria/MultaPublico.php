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
    public $valorUr;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'articulo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        try {
            $response = Http::get('https://www.gub.uy/direccion-general-impositiva/datos-y-estadisticas/datos/unidad-reajustable-ur');
            if ($response->successful()) {
                $html = $response->body();
                $dom = new DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new DOMXPath($dom);
                $mesActual = ucfirst(now()->monthName);
                $valor = $xpath->query("//table/tbody/tr[contains(td[1], '$mesActual')]/td[2]");
                if ($valor->length > 0) {
                    $this->valorUr = trim($valor->item(0)->nodeValue);
                }
            }
        } catch (\Exception $e) {
            // Silently fail, so the page still loads
        }
    }

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
