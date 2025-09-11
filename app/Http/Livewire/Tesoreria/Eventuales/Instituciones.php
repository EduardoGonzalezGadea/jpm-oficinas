<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use App\Models\Tesoreria\EventualInstitucion;
use Livewire\Component;
use Livewire\WithPagination;

class Instituciones extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $institucion_id;
    public $nombre;
    public $descripcion;
    public $activa = true;
    public $search = '';

    public $selectedInstitucion;

    protected $rules = [
        'nombre' => 'required|string|max:255|unique:tes_eventuales_instituciones,nombre',
        'descripcion' => 'nullable|string|max:500',
        'activa' => 'boolean',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.unique' => 'Ya existe una institución con este nombre.',
        'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
        'descripcion.max' => 'La descripción no puede tener más de 500 caracteres.',
    ];

    protected $listeners = [
        'resetForm' => 'resetForm',
        'destroy' => 'destroy',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatchBrowserEvent('show-modal', ['modal' => '#institucionModal']);
    }

    public function store()
    {
        $this->validate();

        EventualInstitucion::create([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activa' => $this->activa,
        ]);

        $this->resetForm();
        $this->dispatchBrowserEvent('close-modal', ['modal' => '#institucionModal']);
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => 'Institución creada exitosamente.'
        ]);
    }

    public function edit($id)
    {
        $institucion = EventualInstitucion::findOrFail($id);
        
        $this->institucion_id = $institucion->id;
        $this->nombre = $institucion->nombre;
        $this->descripcion = $institucion->descripcion;
        $this->activa = $institucion->activa;

        $this->rules['nombre'] = 'required|string|max:255|unique:tes_eventuales_instituciones,nombre,' . $this->institucion_id;

        $this->dispatchBrowserEvent('show-modal', ['modal' => '#institucionModal']);
    }

    public function update()
    {
        $this->rules['nombre'] = 'required|string|max:255|unique:tes_eventuales_instituciones,nombre,' . $this->institucion_id;
        $this->validate();

        $institucion = EventualInstitucion::findOrFail($this->institucion_id);
        $institucion->update([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activa' => $this->activa,
        ]);

        $this->resetForm();
        $this->dispatchBrowserEvent('close-modal', ['modal' => '#institucionModal']);
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => 'Institución actualizada exitosamente.'
        ]);
    }

    public function showDetails($id)
    {
        $this->selectedInstitucion = EventualInstitucion::with('eventuales')->findOrFail($id);
    }

    public function resetDetails()
    {
        $this->selectedInstitucion = null;
    }

    public function destroy($id)
    {
        try {
            $institucion = EventualInstitucion::findOrFail($id);
            
            // Verificar si la institución tiene eventuales asociados
            if ($institucion->eventuales()->count() > 0) {
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'error',
                    'message' => 'No se puede eliminar la institución porque tiene eventuales asociados.'
                ]);
                return;
            }

            $institucion->delete();
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => 'Institución eliminada exitosamente.'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error al eliminar la institución.'
            ]);
        }
    }

    public function toggleActiva($id)
    {
        $institucion = EventualInstitucion::findOrFail($id);
        $institucion->update(['activa' => !$institucion->activa]);
        
        $status = $institucion->activa ? 'activada' : 'desactivada';
        $this->dispatchBrowserEvent('alert', [
            'type' => 'success',
            'message' => "Institución {$status} exitosamente."
        ]);
    }

    public function resetForm()
    {
        $this->institucion_id = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->activa = true;
        $this->resetErrorBag();
        $this->rules['nombre'] = 'required|string|max:255|unique:tes_eventuales_instituciones,nombre';
    }

    public function render()
    {
        $instituciones = EventualInstitucion::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.tesoreria.eventuales.instituciones', compact('instituciones'));
    }
}