<?php

namespace App\Http\Livewire\Tesoreria\Valores\TipoLibreta;

use App\Models\Tesoreria\Servicio;
use App\Models\Tesoreria\TipoLibreta;
use App\Traits\ConvertirMayusculas;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, ConvertirMayusculas;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $tipoLibretaId;
    public $tipoLibretaIdToDelete;
    public $nombre, $cantidad_recibos, $stock_minimo_recibos;
    public $serviciosSeleccionados = [];
    public $allServicios;

    protected $rules = [
        'nombre' => 'required|string|max:255|unique:tes_tipos_libretas,nombre',
        'cantidad_recibos' => 'required|integer|in:25,50',
        'stock_minimo_recibos' => 'required|integer|min:0',
        'serviciosSeleccionados' => 'array'
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function mount()
    {
        $this->allServicios = Servicio::where('activo', true)->orderBy('nombre')->get();
    }

    public function render()
    {
        $tiposLibreta = TipoLibreta::with('servicios')
            ->where('nombre', 'like', '%' . $this->search . '%')
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.tesoreria.valores.tipo-libreta.index', compact('tiposLibreta'))
            ->extends('layouts.app')
            ->section('content');
    }

    public function create()
    {
        $this->resetInput();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $tipoLibreta = TipoLibreta::with('servicios')->findOrFail($id);
        $this->tipoLibretaId = $id;
        $this->nombre = $tipoLibreta->nombre;
        $this->cantidad_recibos = $tipoLibreta->cantidad_recibos;
        $this->stock_minimo_recibos = $tipoLibreta->stock_minimo_recibos;
        $this->serviciosSeleccionados = $tipoLibreta->servicios->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function save()
    {
        $this->rules['nombre'] = 'required|string|max:255|unique:tes_tipos_libretas,nombre,' . $this->tipoLibretaId;
        $this->validate();

        // Convertir nombre a mayúsculas
        $nombre = $this->toUpper($this->nombre);

        $tipoLibreta = TipoLibreta::updateOrCreate(
            ['id' => $this->tipoLibretaId],
            [
                'nombre' => $nombre,
                'cantidad_recibos' => $this->cantidad_recibos,
                'stock_minimo_recibos' => $this->stock_minimo_recibos,
            ]
        );

        $tipoLibreta->servicios()->sync($this->serviciosSeleccionados);

        // Invalidar caché de tipos de libretas para que se reflejen los cambios
        Cache::forget('tipos_libreta_all');

        $this->dispatchBrowserEvent('swal', [
            'title' => 'Éxito',
            'text' => 'Tipo de libreta guardado correctamente.',
            'type' => 'success'
        ]);

        $this->showModal = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInput();
    }

    public function confirmDelete($id)
    {
        $this->tipoLibretaIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->tipoLibretaIdToDelete = null;
    }

    public function destroy()
    {
        TipoLibreta::find($this->tipoLibretaIdToDelete)->delete();

        // Invalidar caché de tipos de libretas para que se reflejen los cambios
        Cache::forget('tipos_libreta_all');

        $this->showDeleteModal = false;

        $this->dispatchBrowserEvent('swal', [
            'title' => 'Éxito',
            'text' => 'Tipo de libreta eliminado correctamente.',
            'type' => 'success'
        ]);
    }

    private function resetInput()
    {
        $this->tipoLibretaId = null;
        $this->nombre = '';
        $this->cantidad_recibos = 25;
        $this->stock_minimo_recibos = 0;
        $this->serviciosSeleccionados = [];
    }
}
