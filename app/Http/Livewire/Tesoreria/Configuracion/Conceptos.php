<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\Concepto;
use Livewire\Component;
use Livewire\WithPagination;

class Conceptos extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $concepto_id;
    public $nombre, $codigo_siif, $tipo = 'Ingreso', $requiere_institucion = false, $activo = true;

    protected $listeners = ['deleteConcepto'];

    protected function rules()
    {
        return [
            'nombre'               => 'required|string|max:150|unique:tes_conceptos,nombre,' . $this->concepto_id,
            'codigo_siif'          => 'nullable|string|max:50',
            'tipo'                 => 'required|in:Ingreso,Egreso,Ambos',
            'requiere_institucion' => 'boolean',
            'activo'               => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Concepto::query();

        if ($this->search) {
            $query->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('codigo_siif', 'like', '%' . $this->search . '%')
                ->orWhere('tipo', 'like', '%' . $this->search . '%');
        }

        $conceptos = $query->orderBy('nombre')->paginate(10);

        return view('livewire.tesoreria.configuracion.conceptos', compact('conceptos'));
    }

    public function resetForm()
    {
        $this->reset(['concepto_id', 'nombre', 'codigo_siif', 'tipo', 'requiere_institucion']);
        $this->tipo = 'Ingreso';
        $this->requiere_institucion = false;
        $this->activo = true;
        $this->resetErrorBag();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalConcepto']);
    }

    public function edit(int $id)
    {
        $this->resetForm();
        $concepto = Concepto::findOrFail($id);
        $this->concepto_id = $concepto->id;
        $this->nombre = $concepto->nombre;
        $this->codigo_siif = $concepto->codigo_siif;
        $this->tipo = $concepto->tipo;
        $this->requiere_institucion = $concepto->requiere_institucion;
        $this->activo = $concepto->activo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalConcepto']);
    }

    public function store()
    {
        $this->validate();

        Concepto::updateOrCreate(
            ['id' => $this->concepto_id],
            [
                'nombre'               => $this->nombre,
                'codigo_siif'          => $this->codigo_siif,
                'tipo'                 => $this->tipo,
                'requiere_institucion' => $this->requiere_institucion,
                'activo'               => $this->activo,
            ]
        );

        $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalConcepto']);
        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Éxito',
            'text'  => 'Concepto guardado correctamente.',
        ]);
        $this->resetForm();
    }

    public function confirmDelete(int $id)
    {
        $this->dispatchBrowserEvent('confirm-delete', [
            'id'    => $id,
            'event' => 'deleteConcepto',
            'title' => '¿Eliminar concepto?',
            'text'  => 'Se realizará una eliminación lógica (soft-delete).',
        ]);
    }

    public function deleteConcepto($id)
    {
        $concepto = Concepto::findOrFail($id);
        $concepto->delete();

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Eliminado',
            'text'  => 'El concepto ha sido eliminado.',
        ]);
    }
}
