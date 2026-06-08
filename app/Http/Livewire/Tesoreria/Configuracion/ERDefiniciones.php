<?php

namespace App\Http\Livewire\Tesoreria\Configuracion;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\ERDefinicion;
use App\Models\Tesoreria\Concepto;
use App\Models\Tesoreria\Institucion222;
use Illuminate\Support\Facades\DB;

class ERDefiniciones extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $er_definicion_id;
    public $nombre, $codigo, $tipo_recaudacion = 'LD', $institucion_222_id, $turno, $activo = true, $orden = 0;
    public $conceptos_seleccionados = [];

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'codigo' => 'required|string|max:50|unique:tes_er_definiciones,codigo',
        'tipo_recaudacion' => 'required|in:LD,222',
        'orden' => 'required|integer|min:0',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->dispatchBrowserEvent('openModal', ['id' => 'modalERDefinicion']);
    }

    public function resetInputFields()
    {
        $this->er_definicion_id = null;
        $this->nombre = '';
        $this->codigo = '';
        $this->tipo_recaudacion = 'LD';
        $this->institucion_222_id = null;
        $this->turno = '';
        $this->activo = true;
        $this->orden = 0;
        $this->conceptos_seleccionados = [];
        $this->resetErrorBag();
    }

    public function edit($id)
    {
        $definicion = ERDefinicion::with('conceptos')->findOrFail($id);
        $this->er_definicion_id = $id;
        $this->nombre = $definicion->nombre;
        $this->codigo = $definicion->codigo;
        $this->tipo_recaudacion = $definicion->tipo_recaudacion;
        $this->institucion_222_id = $definicion->institucion_222_id;
        $this->turno = $definicion->turno;
        $this->activo = $definicion->activo;
        $this->orden = $definicion->orden;
        $this->conceptos_seleccionados = $definicion->conceptos->pluck('id')->toArray();

        $this->dispatchBrowserEvent('openModal', ['id' => 'modalERDefinicion']);
    }

    public function store()
    {
        $rules = $this->rules;
        if ($this->er_definicion_id) {
            $rules['codigo'] = 'required|string|max:50|unique:tes_er_definiciones,codigo,' . $this->er_definicion_id;
        }

        $this->validate($rules);

        DB::beginTransaction();
        try {
            $data = [
                'nombre' => $this->nombre,
                'codigo' => strtoupper($this->codigo),
                'tipo_recaudacion' => $this->tipo_recaudacion,
                'institucion_222_id' => $this->tipo_recaudacion == '222' ? $this->institucion_222_id : null,
                'turno' => $this->turno,
                'activo' => $this->activo,
                'orden' => $this->orden,
            ];

            $definicion = ERDefinicion::updateOrCreate(['id' => $this->er_definicion_id], $data);
            $definicion->conceptos()->sync($this->conceptos_seleccionados);

            DB::commit();

            $this->dispatchBrowserEvent('closeModal', ['id' => 'modalERDefinicion']);
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'success',
                'title' => 'Éxito',
                'text' => $this->er_definicion_id ? 'Definición actualizada.' : 'Definición creada.',
            ]);
            $this->resetInputFields();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('swal', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Ocurrió un error al guardar: ' . $e->getMessage(),
            ]);
        }
    }

    public function confirmDelete($id)
    {
        $this->dispatchBrowserEvent('swal-confirm', [
            'icon' => 'warning',
            'title' => '¿Estás seguro?',
            'text' => 'Se eliminará esta definición de ER.',
            'id' => $id,
            'method' => 'delete'
        ]);
    }

    protected $listeners = ['delete' => 'delete'];

    public function delete($id)
    {
        ERDefinicion::findOrFail($id)->delete();
        $this->dispatchBrowserEvent('swal', [
            'icon' => 'success',
            'title' => 'Eliminado',
            'text' => 'La definición ha sido eliminada.',
        ]);
    }

    public function render()
    {
        $definiciones = ERDefinicion::with(['institucion222', 'conceptos'])
            ->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('codigo', 'like', '%' . $this->search . '%');
            })
            ->orderBy('orden')
            ->paginate(10);

        return view('livewire.tesoreria.configuracion.er-definiciones', [
            'definiciones' => $definiciones,
            'instituciones' => Institucion222::activos()->get(),
            'conceptos' => Concepto::where('tipo', '!=', 'Egreso')->where('activo', true)->get(),
        ]);
    }
}
