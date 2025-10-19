<?php

namespace App\Http\Livewire\Tesoreria\CajaDiaria;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tesoreria\CajaDiaria\ConceptoCobro;
use App\Models\Tesoreria\CajaDiaria\ConceptoCobroCampo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConceptosCobro extends Component
{
    use WithPagination;

    public $search = '';
    public $cant = 10;
    public $modal = false;
    public $idConcepto = null;
    public $nombre = '';
    public $descripcion = '';
    public $activo = true;

    // Para campos
    public $campos = [];
    public $campoModal = false;
    public $campoId = null;
    public $campoNombre = '';
    public $campoTitulo = '';
    public $campoTipo = 'text';
    public $campoRequerido = false;
    public $campoOpciones = '';
    public $campoOrden = 0;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCant()
    {
        $this->resetPage();
    }

    public function render()
    {
        $conceptos = ConceptoCobro::search($this->search)
            ->ordenado()
            ->paginate($this->cant);

        return view('livewire.tesoreria.caja-diaria.conceptos-cobro', [
            'conceptos' => $conceptos
        ]);
    }

    public function openModal()
    {
        $this->resetForm();
        $this->modal = true;
    }

    public function closeModal()
    {
        $this->modal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->idConcepto = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->activo = true;
    }

    public function store()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'boolean'
        ]);

        DB::transaction(function () {
            $data = [
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => $this->activo,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ];

            if ($this->idConcepto) {
                $concepto = ConceptoCobro::find($this->idConcepto);
                $concepto->update($data);
                session()->flash('message', 'Concepto actualizado correctamente.');
            } else {
                ConceptoCobro::create($data);
                session()->flash('message', 'Concepto creado correctamente.');
            }
        });

        $this->closeModal();
        $this->emit('refreshComponent');
    }

    public function edit($id)
    {
        $concepto = ConceptoCobro::find($id);
        $this->idConcepto = $concepto->id;
        $this->nombre = $concepto->nombre;
        $this->descripcion = $concepto->descripcion;
        $this->activo = $concepto->activo;
        $this->modal = true;
    }

    public function delete($id)
    {
        $concepto = ConceptoCobro::find($id);
        if ($concepto->cobros()->count() > 0) {
            session()->flash('error', 'No se puede eliminar el concepto porque tiene cobros asociados.');
            return;
        }

        $concepto->delete();
        session()->flash('message', 'Concepto eliminado correctamente.');
        $this->emit('refreshComponent');
    }

    // Métodos para campos
    public function openCampoModal($conceptoId)
    {
        $this->idConcepto = $conceptoId;
        $this->resetCampoForm();
        $this->campoModal = true;
        $this->loadCampos();
    }

    public function closeCampoModal()
    {
        $this->campoModal = false;
        $this->resetCampoForm();
    }

    public function resetCampoForm()
    {
        $this->campoId = null;
        $this->campoNombre = '';
        $this->campoTitulo = '';
        $this->campoTipo = 'text';
        $this->campoRequerido = false;
        $this->campoOpciones = '';
        $this->campoOrden = 0;
    }

    public function loadCampos()
    {
        $this->campos = ConceptoCobroCampo::where('concepto_id', $this->idConcepto)
            ->orderBy('orden')
            ->get()
            ->toArray();
    }

    public function addCampo()
    {
        $this->resetCampoForm();
    }

    public function editCampo($campoId)
    {
        $campo = ConceptoCobroCampo::find($campoId);
        $this->campoId = $campo->id;
        $this->campoNombre = $campo->nombre;
        $this->campoTitulo = $campo->titulo;
        $this->campoTipo = $campo->tipo;
        $this->campoRequerido = $campo->requerido;
        $this->campoOpciones = is_array($campo->opciones) ? implode("\n", $campo->opciones) : '';
        $this->campoOrden = $campo->orden;
    }

    public function storeCampo()
    {
        $this->validate([
            'campoNombre' => 'required|string|max:255',
            'campoTitulo' => 'nullable|string|max:255',
            'campoTipo' => 'required|in:text,number,date,select,textarea,checkbox',
            'campoOrden' => 'required|integer|min:0'
        ]);

        $opciones = null;
        if ($this->campoTipo === 'select' && !empty($this->campoOpciones)) {
            $opciones = array_filter(array_map('trim', explode("\n", $this->campoOpciones)));
        }

        $data = [
            'concepto_id' => $this->idConcepto,
            'nombre' => $this->campoNombre,
            'titulo' => $this->campoTitulo,
            'tipo' => $this->campoTipo,
            'requerido' => $this->campoRequerido,
            'opciones' => $opciones,
            'orden' => $this->campoOrden,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ];

        if ($this->campoId) {
            $campo = ConceptoCobroCampo::find($this->campoId);
            $campo->update($data);
        } else {
            ConceptoCobroCampo::create($data);
        }

        $this->loadCampos();
        $this->resetCampoForm();
        session()->flash('message', 'Campo guardado correctamente.');
    }

    public function deleteCampo($campoId)
    {
        ConceptoCobroCampo::find($campoId)->delete();
        $this->loadCampos();
        session()->flash('message', 'Campo eliminado correctamente.');
    }

    public function order($column)
    {
        // Implementar ordenamiento si es necesario
    }
}
