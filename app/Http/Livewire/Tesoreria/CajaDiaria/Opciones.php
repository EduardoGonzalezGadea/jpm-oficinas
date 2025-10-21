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
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Concepto actualizado correctamente.']);
        } else {
            ConceptoPago::create([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);
            $this->dispatchBrowserEvent('swal:success', ['text' => 'Concepto creado correctamente.']);
        }
        $this->dispatchBrowserEvent('hide-modal-concepto');
        $this->reset(['conceptoId', 'nombre', 'descripcion', 'activo']);
    }

    public function eliminarConcepto($id)
    {
        $this->dispatchBrowserEvent('swal:confirm', [
            'title' => '¿Está seguro?',
            'text' => '¡No podrás revertir esto!',
            'icon' => 'warning',
            'confirmButtonText' => 'Sí, eliminar',
            'method' => 'confirmDeleteConcepto',
            'id' => $id
        ]);
    }

    public function confirmDeleteConcepto($id)
    {
        $concepto = ConceptoPago::findOrFail($id);
        $concepto->delete();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Concepto eliminado correctamente.']);
    }

    public function toggleActivo($id)
    {
        $concepto = ConceptoPago::findOrFail($id);
        $concepto->activo = !$concepto->activo;
        $concepto->save();
        $this->dispatchBrowserEvent('swal:success', ['text' => 'Estado actualizado.']);
    }
}
