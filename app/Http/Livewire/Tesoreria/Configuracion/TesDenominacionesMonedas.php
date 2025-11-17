<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use App\Models\Tesoreria\TesDenominacionMoneda as Model;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class TesDenominacionesMonedas extends Component
{
    use WithPagination;

    protected $listeners = ['resetForm', 'destroy' => 'destroy', 'refreshComponent' => '$refresh'];

    protected $paginationTheme = 'bootstrap';

    public $search;
    public $denominacion_moneda_id, $tipo_moneda, $denominacion, $descripcion, $activo;
    public $selectedDenominacion = null;

    // Opciones para el dropdown de tipo de moneda
    public $tiposMoneda = [
        'Billetes' => 'Billetes',
        'Monedas' => 'Monedas',
        'Billetes extranjeros' => 'Billetes extranjeros',
        'Monedas extranjeras' => 'Monedas extranjeras'
    ];

    public function mount()
    {
        $this->activo = true;
        $this->tipo_moneda = 'Billetes'; // Valor por defecto
    }

    public function render()
    {
        // Verificar autenticación antes de procesar cualquier lógica
        if (!auth()->check()) {
            $this->dispatchBrowserEvent('redirect-to-login', [
                'message' => 'La sesión ha expirado. Por favor, inicie sesión de nuevo.'
            ]);
            return view('livewire.tesoreria.configuracion.tes-denominaciones-monedas', [
                'denominaciones' => collect(),
            ]);
        }

        $page = $this->page ?: 1;
        $cacheKey = 'denominaciones_search_' . $this->search . '_page_' . $page;

        $denominaciones = Cache::remember($cacheKey, now()->addDay(), function () {
            return Model::search($this->search)
                ->ordenado()
                ->paginate(15);
        });

        return view('livewire.tesoreria.configuracion.tes-denominaciones-monedas', [
            'denominaciones' => $denominaciones,
        ]);
    }

    public function create()
    {
        $this->resetInput();
        $this->dispatchBrowserEvent('show-modal', ['id' => 'denominacionModal']);
    }

    public function store()
    {
        $this->validate([
            'tipo_moneda' => 'required|string|max:100',
            'denominacion' => 'required|numeric|min:0|max:999999.99',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        Model::create([
            'tipo_moneda' => $this->tipo_moneda,
            'denominacion' => $this->denominacion,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ]);

        Cache::flush();
        $this->resetInput();
        $this->emit('denominacionStore');
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Denominación creada con éxito!', 'toast' => true]);
    }

    public function edit($id)
    {
        $denominacion = Model::findOrFail($id);

        $this->denominacion_moneda_id = $id;
        $this->tipo_moneda = $denominacion->tipo_moneda;
        $this->denominacion = $denominacion->denominacion;
        $this->descripcion = $denominacion->descripcion;
        $this->activo = $denominacion->activo;

        $this->dispatchBrowserEvent('show-modal', ['id' => 'denominacionModal']);
    }

    public function update()
    {
        $this->validate([
            'tipo_moneda' => 'required|string|max:100',
            'denominacion' => 'required|numeric|min:0|max:999999.99',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        if ($this->denominacion_moneda_id) {
            $denominacion = Model::findOrFail($this->denominacion_moneda_id);
            $denominacion->update([
                'tipo_moneda' => $this->tipo_moneda,
                'denominacion' => $this->denominacion,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
            ]);
            Cache::flush();
            $this->resetInput();
            $this->emit('denominacionUpdate');
            $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Denominación actualizada con éxito!', 'toast' => true]);
        }
    }

    public function destroy($id)
    {
        $denominacion = Model::findOrFail($id);
        $denominacion->delete();
        Cache::flush();
        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Denominación eliminada con éxito!', 'toast' => true]);
    }

    public function showDetails($id)
    {
        $this->selectedDenominacion = Model::findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedDenominacion = null;
    }

    public function resetForm()
    {
        $this->resetInput();
    }

    private function resetInput()
    {
        $this->denominacion_moneda_id = null;
        $this->tipo_moneda = 'Billetes';
        $this->denominacion = null;
        $this->descripcion = null;
        $this->activo = true;
    }

    public function updatingSearch()
    {
        $this->resetPage();
        Cache::flush();
    }
}
