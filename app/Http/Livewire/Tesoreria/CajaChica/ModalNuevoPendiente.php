<?php

namespace App\Http\Livewire\Tesoreria\CajaChica;

use Livewire\Component;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Dependencia;

class ModalNuevoPendiente extends Component
{
    public $idCajaChica;
    public $pendiente;
    public $fechaPendientes;
    public $relDependencia;
    public $montoPendientes;
    public $dependencias = [];
    public $mostrarModal = false;

    protected $rules = [
        'pendiente' => 'required|integer|min:1',
        'fechaPendientes' => 'required|date',
        'relDependencia' => 'required|exists:tes_cch_dependencias,idDependencias',
        'montoPendientes' => 'required|numeric|min:0.01',
    ];

    protected $listeners = [
        'mostrarModalNuevoPendiente' => 'abrirModal',
        'cerrarModalNuevoPendiente' => 'cerrarModal',
    ];

    public function mount() {}

    public function abrirModal($idCajaChica)
    {
        $this->reset(['pendiente', 'fechaPendientes', 'relDependencia', 'montoPendientes']);
        $this->idCajaChica = $idCajaChica;
        $this->fechaPendientes = now()->toDateString();
        $this->cargarDependencias();
        $this->determinarNumeroPendienteSiguiente();
        $this->mostrarModal = true;

        // Usar el sistema global de modales como en valores/entradas
        $this->dispatchBrowserEvent('show-modal', ['id' => 'modalNuevoPendiente']);
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->reset(['pendiente', 'fechaPendientes', 'relDependencia', 'montoPendientes']);
        $this->resetErrorBag();
    }

    public function cargarDependencias()
    {
        $this->dependencias = Dependencia::orderBy('dependencia', 'ASC')->get();
    }

    public function determinarNumeroPendienteSiguiente()
    {
        if (!$this->idCajaChica) {
            $this->pendiente = 1;
            return;
        }

        $cajaChica = CajaChica::find($this->idCajaChica);
        if (!$cajaChica) {
            $this->pendiente = 1;
            return;
        }

        $anio = $cajaChica->anio;
        $maxPendiente = Pendiente::whereHas('cajaChica', function ($query) use ($anio) {
            $query->where('anio', $anio);
        })
            ->max('pendiente');

        $this->pendiente = $maxPendiente ? $maxPendiente + 1 : 1;
    }

    public function guardar()
    {
        $this->validate();

        if (!$this->idCajaChica) {
            session()->flash('error', 'No se ha seleccionado un Fondo Permanente válido.');
            return;
        }

        $existe = Pendiente::where('relCajaChica', $this->idCajaChica)
            ->where('pendiente', $this->pendiente)
            ->exists();

        if ($existe) {
            $this->addError('pendiente', 'Ya existe un Pendiente con ese número para este Fondo Permanente.');
            return;
        }

        try {
            Pendiente::create([
                'relCajaChica' => $this->idCajaChica,
                'pendiente' => $this->pendiente,
                'fechaPendientes' => $this->fechaPendientes,
                'relDependencia' => $this->relDependencia,
                'montoPendientes' => $this->montoPendientes,
            ]);

            session()->flash('message', 'Pendiente creado correctamente.');
            $this->dispatchBrowserEvent('hide-modal', ['id' => 'modalNuevoPendiente']);
            $this->emitTo('tesoreria.caja-chica.index', 'pendienteCreado');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear el pendiente.');
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.caja-chica.modal-nuevo-pendiente');
    }
}
