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
    public $medio_de_pago_id, $nombre, $nombre_corto, $descripcion, $activo;
    public $es_libro_diario, $es_recaudacion, $orden, $contado, $codigo_soniar;
    public $selectedMedioDePago = null;

    public function mount()
    {
        $this->activo = true;
        $this->es_libro_diario = true;
        $this->es_recaudacion = true;
        $this->orden = 0;
        $this->contado = false;
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
        $this->dispatchBrowserEvent('show-modal', ['id' => 'medioDePagoModal']);
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre',
            'nombre_corto' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'es_libro_diario' => 'boolean',
            'es_recaudacion' => 'boolean',
            'orden' => 'nullable|integer|min:0',
            'contado' => 'boolean',
            'codigo_soniar' => 'nullable|string|max:50',
        ]);

        Model::create([
            'nombre' => $this->nombre,
            'nombre_corto' => $this->nombre_corto,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
            'es_libro_diario' => $this->es_libro_diario,
            'es_recaudacion' => $this->es_recaudacion,
            'orden' => $this->orden ?? 0,
            'contado' => $this->contado,
            'codigo_soniar' => $this->codigo_soniar,
        ]);

        \Illuminate\Support\Facades\Cache::forget('medios_de_pago_activos');

        $this->resetInput();
        $this->emit('medioDePagoStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio de pago creado con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $medioDePago = Model::findOrFail($id);

        $this->medio_de_pago_id = $id;
        $this->nombre = $medioDePago->nombre;
        $this->nombre_corto = $medioDePago->nombre_corto;
        $this->descripcion = $medioDePago->descripcion;
        $this->activo = $medioDePago->activo;
        $this->es_libro_diario = $medioDePago->es_libro_diario;
        $this->es_recaudacion = $medioDePago->es_recaudacion;
        $this->orden = $medioDePago->orden;
        $this->contado = $medioDePago->contado;
        $this->codigo_soniar = $medioDePago->codigo_soniar;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'medioDePagoModal']);
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tes_medio_de_pagos,nombre,' . $this->medio_de_pago_id,
            'nombre_corto' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
            'es_libro_diario' => 'boolean',
            'es_recaudacion' => 'boolean',
            'orden' => 'nullable|integer|min:0',
            'contado' => 'boolean',
            'codigo_soniar' => 'nullable|string|max:50',
        ]);

        if ($this->medio_de_pago_id) {
            $medioDePago = Model::findOrFail($this->medio_de_pago_id);
            $medioDePago->update([
                'nombre' => $this->nombre,
                'nombre_corto' => $this->nombre_corto,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
                'es_libro_diario' => $this->es_libro_diario,
                'es_recaudacion' => $this->es_recaudacion,
                'orden' => $this->orden ?? 0,
                'contado' => $this->contado,
                'codigo_soniar' => $this->codigo_soniar,
            ]);
            \Illuminate\Support\Facades\Cache::forget('medios_de_pago_activos');
            $this->resetInput();
            $this->emit('medioDePagoUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Medio de pago actualizado con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $medioDePago = Model::findOrFail($id);

        // Verificar si el medio de pago está siendo usado (FK en todas las tablas)
        $enUso = DB::table('tes_arrendamientos')->where('medio_pago_id', $id)->exists() ||
            DB::table('tes_eventuales')->where('medio_pago_id', $id)->exists() ||
            DB::table('tes_multas_cobradas')->where('medio_pago_id', $id)->exists() ||
            DB::table('tes_cfe_medios_pago')->where('medio_pago_id', $id)->exists() ||
            DB::table('tes_libro_diario')->where('medio_id', $id)->exists() ||
            DB::table('tes_prendas')->where('medio_pago_id', $id)->exists() ||
            DB::table('tes_multa_medios_pago')->where('medio_pago_id', $id)->exists();

        if ($enUso) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'No se puede eliminar el medio de pago porque está siendo utilizado.'
            ]);
            return;
        }

        $medioDePago->delete();
        \Illuminate\Support\Facades\Cache::forget('medios_de_pago_activos');
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
        $this->nombre_corto = null;
        $this->descripcion = null;
        $this->activo = true;
        $this->es_libro_diario = true;
        $this->es_recaudacion = true;
        $this->orden = 0;
        $this->contado = false;
        $this->codigo_soniar = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}
