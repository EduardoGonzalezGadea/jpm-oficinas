<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;


use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\CajaDiaria\ConceptoPago;


class Opciones extends Component
{
    use WithPagination;

    public $fecha;
    public $search = '';
    public $conceptoId = null;
    public $nombre;
    public $descripcion;
    public $activo = true;

    protected $paginationTheme = 'bootstrap';

    public function mount($fecha = null)
    {
        $this->fecha = $fecha ?: now()->format('Y-m-d');
    }

    public function render()
    {
        $conceptos = ConceptoPago::query()
            ->where('nombre', 'like', '%'.$this->search.'%')
            ->orderBy('nombre')
            ->paginate(10);
        return view('livewire.tesoreria.caja-diaria.opciones', [
            'conceptos' => $conceptos,
            'conceptoId' => $this->conceptoId,
        ]);
    }

    public function crearConcepto()
    {
        $this->reset(['conceptoId', 'nombre', 'descripcion', 'activo']);
        $this->activo = true;
        $this->dispatchBrowserEvent('show-modal-concepto');
    }

    public function editarConcepto($id)
    {
        $concepto = ConceptoPago::findOrFail($id);
        $this->conceptoId = $concepto->id;
        $this->nombre = $concepto->nombre;
        $this->descripcion = $concepto->descripcion;
        $this->activo = $concepto->activo;
        $this->dispatchBrowserEvent('show-modal-concepto');
    }

    public function guardarConcepto()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
        ]);

        if ($this->conceptoId) {
            $concepto = ConceptoPago::findOrFail($this->conceptoId);
            $concepto->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);
            session()->flash('message', 'Concepto actualizado correctamente.');
        } else {
            ConceptoPago::create([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);
            session()->flash('message', 'Concepto creado correctamente.');
        }
        $this->dispatchBrowserEvent('hide-modal-concepto');
        $this->reset(['conceptoId', 'nombre', 'descripcion', 'activo']);
    }

    public function eliminarConcepto($id)
    {
        $concepto = ConceptoPago::findOrFail($id);
        $concepto->delete();
        session()->flash('message', 'Concepto eliminado correctamente.');
    }

    public function toggleActivo($id)
    {
        $concepto = ConceptoPago::findOrFail($id);
        $concepto->activo = !$concepto->activo;
        $concepto->save();
        session()->flash('message', 'Estado actualizado.');
    }
}
