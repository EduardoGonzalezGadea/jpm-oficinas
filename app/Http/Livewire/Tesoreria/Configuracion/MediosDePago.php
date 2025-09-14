<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\MedioDePago as Model;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class MediosDePago extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $medio_de_pago_id, $nombre, $descripcion, $activo;
    public $selectedMedioDePago = null;

    public function mount()
    {
        $this->activo = true;
    }

    public function render()
    {
        // Verificar autenticación antes de procesar cualquier lógica
        if (!auth()->check()) {
            $this->dispatchBrowserEvent('redirect-to-login', [
                'message' => 'La sesión ha expirado. Por favor, inicie sesión de nuevo.'
            ]);
            return view('livewire.tesoreria.configuracion.medios-de-pago', [
                'mediosDePago' => collect(),
            ]);
        }

        $mediosDePago = Model::search($this->search)
            ->ordenado()
            ->paginate(10);

        return view('livewire.tesoreria.configuracion.medios-de-pago', [
            'mediosDePago' => $mediosDePago,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'medioDePagoModal']);
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ]);

        $this->resetInput();
        $this->emit('medioDePagoStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio de pago creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $medioDePago = Model::findOrFail($id);

        $this->medio_de_pago_id = $id;
        $this->nombre = $medioDePago->nombre;
        $this->descripcion = $medioDePago->descripcion;
        $this->activo = $medioDePago->activo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'medioDePagoModal']);
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre,' . $this->medio_de_pago_id,
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        if ($this->medio_de_pago_id) {
            $medioDePago = Model::findOrFail($this->medio_de_pago_id);
            $medioDePago->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);
            $this->resetInput();
            $this->emit('medioDePagoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio de pago actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $medioDePago = Model::findOrFail($id);

        // Verificar si el medio de pago está siendo usado
        $enUso = DB::table('tes_arrendamientos')->where('medio_de_pago', $medioDePago->nombre)->exists() ||
                 DB::table('tes_eventuales')->where('medio_de_pago', $medioDePago->nombre)->exists();

        if ($enUso) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar el medio de pago porque está siendo utilizado.'
            ]);
            return;
        }

        $medioDePago->delete();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio de pago eliminado con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedMedioDePago = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedMedioDePago = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->medio_de_pago_id = null;
        $this->nombre = null;
        $this->descripcion = null;
        $this->activo = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
