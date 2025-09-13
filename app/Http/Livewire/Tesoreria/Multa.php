<?php

namespace App\Http\Livewire\Tesoreria;

use App\Models\Tesoreria\Multa as MultaModel;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;

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
    public $valorUr;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'articulo'],
        'sortDirection' => ['except' => 'asc'],
        'page' => ['except' => 1],
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
        try {
            $url = 'https://www.bps.gub.uy/bps/valores.jsp';
            $command = 'curl -s --compressed "' . $url . '"';
            $html = shell_exec($command);

            if (!empty($html)) {
                $pattern = '/Unidad Reajustable \(UR\).*?<td class="celda-numero">\$(.*?)<\/td>.*?<td class="celda-numero">\$(.*?)<\/td>/s';
                if (preg_match($pattern, $html, $matches)) {
                    // El valor actual es el segundo que se captura
                    $this->valorUr = trim($matches[2]);
                } else {
                    $this->valorUr = 'No disponible';
                }
            } else {
                $this->valorUr = 'Error al cargar';
            }
        } catch (\Exception $e) {
            $this->valorUr = 'Error';
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

        MultaModel::updateOrCreate(
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

        session()->flash('message', $this->isEdit ? 'Multa actualizada exitosamente.' : 'Multa creada exitosamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function delete($id)
    {
        MultaModel::find($id)->delete();
        session()->flash('message', 'Multa eliminada exitosamente.');
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

        return view('livewire.tesoreria.multa', compact('multas'));
    }
}
