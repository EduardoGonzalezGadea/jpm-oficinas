<?php

namespace App\Livewire\Tesoreria\Configuracion;

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
    public $medio_de_pago_id;
    public $nombre;
    public $nombre_corto;
    public $descripcion;
    public $orden = 0;
    public $codigo_soniar;
    public $activo = true;
    public $es_libro_diario = false;
    public $es_recaudacion = false;
    public $contado = false;
    public $selectedMedioDePago = null;

    public function mount()
    {
        $this->activo = true;
    }

    public function render()
    {
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
        $this->dispatch('show-modal', id: 'medioDePagoModal');
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre',
            'nombre_corto' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'codigo_soniar' => 'nullable|string|max:50',
            'activo' => 'boolean',
            'es_libro_diario' => 'boolean',
            'es_recaudacion' => 'boolean',
            'contado' => 'boolean',
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'nombre_corto' => $this->nombre_corto,
            'descripcion' => $this->descripcion,
            'orden' => $this->orden ?? 0,
            'codigo_soniar' => $this->codigo_soniar,
            'activo' => $this->activo ?? true,
            'es_libro_diario' => $this->es_libro_diario ?? false,
            'es_recaudacion' => $this->es_recaudacion ?? false,
            'contado' => $this->contado ?? false,
        ]);

        \Illuminate\Support\Facades\Cache::forget('medios_de_pago_activos');

        $this->resetInput();
        $this->dispatch('medioDePagoStore');
        $this->dispatch('alert', type: 'success', message: 'Medio de pago creado con éxito!', toast: true);
    }

    public function edit($id)
    {
        $medioDePago = Model::findOrFail($id);

        $this->medio_de_pago_id = $id;
        $this->nombre = $medioDePago->nombre;
        $this->nombre_corto = $medioDePago->nombre_corto;
        $this->descripcion = $medioDePago->descripcion;
        $this->orden = $medioDePago->orden;
        $this->codigo_soniar = $medioDePago->codigo_soniar;
        $this->activo = (bool) $medioDePago->activo;
        $this->es_libro_diario = (bool) $medioDePago->es_libro_diario;
        $this->es_recaudacion = (bool) $medioDePago->es_recaudacion;
        $this->contado = (bool) $medioDePago->contado;

        $this->dispatch('show-modal', id: 'medioDePagoModal');
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre,' . $this->medio_de_pago_id,
            'nombre_corto' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'codigo_soniar' => 'nullable|string|max:50',
            'activo' => 'boolean',
            'es_libro_diario' => 'boolean',
            'es_recaudacion' => 'boolean',
            'contado' => 'boolean',
        ]);

        if ($this->medio_de_pago_id) {
            $medioDePago = Model::findOrFail($this->medio_de_pago_id);
            $medioDePago->update([
                'nombre' => $this->nombre,
                'nombre_corto' => $this->nombre_corto,
                'descripcion' => $this->descripcion,
                'orden' => $this->orden ?? 0,
                'codigo_soniar' => $this->codigo_soniar,
                'activo' => $this->activo ?? true,
                'es_libro_diario' => $this->es_libro_diario ?? false,
                'es_recaudacion' => $this->es_recaudacion ?? false,
                'contado' => $this->contado ?? false,
            ]);
            \Illuminate\Support\Facades\Cache::forget('medios_de_pago_activos');
            $this->resetInput();
            $this->dispatch('medioDePagoUpdate');
            $this->dispatch('alert', type: 'success', message: 'Medio de pago actualizado con éxito!', toast: true);
        }
    }

    public function destroy($id)
    {
        $medioDePago = Model::findOrFail($id);

        // Verificar si el medio de pago está siendo usado
        $enUso = DB::table('tes_arrendamientos')->where('medio_de_pago', $medioDePago->nombre)->exists() ||
            DB::table('tes_eventuales')->where('medio_de_pago', $medioDePago->nombre)->exists();

        if ($enUso) {
            $this->dispatch('alert', type: 'error', message: 'No se puede eliminar el medio de pago porque está siendo utilizado.');
            return;
        }

        $medioDePago->delete();
        \Illuminate\Support\Facades\Cache::forget('medios_de_pago_activos');
        $this->dispatch('alert', type: 'success', message: 'Medio de pago eliminado con éxito!', toast: true);
    }

    public function showDetails($id)
    {
        $this->selectedMedioDePago = Model::findOrFail($id);
        $this->dispatch('show-modal', id: 'detailsMedioDePagoModal');
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
        $this->nombre_corto = null;
        $this->descripcion = null;
        $this->orden = 0;
        $this->codigo_soniar = null;
        $this->activo = true;
        $this->es_libro_diario = false;
        $this->es_recaudacion = false;
        $this->contado = false;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
