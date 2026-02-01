<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Pendiente;

use Livewire\Component;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Dependencia;
use Illuminate\Support\Facades\DB;

class ModalEditarDetalle extends Component
{
    public $idPendiente;
    public $relCajaChica;
    public $nroPendiente;
    public $fechaPendientes;
    public $relDependencia;
    public $montoPendientes;

    // Almacenar los valores originales para poder resetear
    public $originalNroPendiente;
    public $originalFechaPendientes;
    public $originalRelDependencia;
    public $originalMontoPendientes;

    protected $listeners = [];

    /**
     * Reglas de validación.
     */
    protected function rules()
    {
        return [
            'nroPendiente' => 'required|numeric|min:1',
            'fechaPendientes' => 'required|date_format:Y-m-d',
            'relDependencia' => 'required|exists:tes_cch_dependencias,idDependencias',
            'montoPendientes' => 'required|numeric|min:0',
        ];
    }

    /**
     * El método Mount carga los datos del pendiente.
     */
    public function mount($id)
    {
        $pendiente = Pendiente::findOrFail($id);

        $this->idPendiente = $pendiente->idPendientes;
        $this->relCajaChica = $pendiente->relCajaChica;
        $this->nroPendiente = $pendiente->pendiente;
        $this->fechaPendientes = $pendiente->fechaPendientes->format('Y-m-d');
        $this->relDependencia = $pendiente->relDependencia;
        $this->montoPendientes = $pendiente->montoPendientes;

        // Guardar los valores originales
        $this->originalNroPendiente = $pendiente->pendiente;
        $this->originalFechaPendientes = $pendiente->fechaPendientes->format('Y-m-d');
        $this->originalRelDependencia = $pendiente->relDependencia;
        $this->originalMontoPendientes = $pendiente->montoPendientes;
    }

    /**
     * Abre el modal.
     */
    public function abrirModal()
    {
        $this->resetearFormulario();
    }

    /**
     * Cierra el modal.
     */
    public function cerrarModal()
    {
        $this->resetErrorBag();
    }

    /**
     * Resetea el formulario a los valores originales.
     */
    public function resetearFormulario()
    {
        // Restaurar las propiedades a sus valores originales
        $this->nroPendiente = $this->originalNroPendiente;
        $this->fechaPendientes = $this->originalFechaPendientes;
        $this->relDependencia = $this->originalRelDependencia;
        $this->montoPendientes = $this->originalMontoPendientes;

        // Limpiar cualquier error de validación
        $this->resetErrorBag();
    }

    /**
     * Guarda los cambios del pendiente.
     */
    public function guardarCambios()
    {
        // Validar los datos
        $this->validate();

        // Verificar si ya existe un pendiente con el mismo número
        $duplicado = Pendiente::where('pendiente', $this->nroPendiente)
            ->where('relCajaChica', $this->relCajaChica)
            ->where('idPendientes', '!=', $this->idPendiente)
            ->exists();

        if ($duplicado) {
            $this->addError('nroPendiente', 'Ya existe otro pendiente con ese número.');
            return;
        }

        DB::beginTransaction();
        try {
            $pendiente = Pendiente::findOrFail($this->idPendiente);
            $pendiente->pendiente = $this->nroPendiente;
            $pendiente->fechaPendientes = $this->fechaPendientes;
            $pendiente->relDependencia = $this->relDependencia;
            $pendiente->montoPendientes = $this->montoPendientes;
            $pendiente->save();

            DB::commit();

            // Actualizar los valores originales
            $this->originalNroPendiente = $this->nroPendiente;
            $this->originalFechaPendientes = $this->fechaPendientes;
            $this->originalRelDependencia = $this->relDependencia;
            $this->originalMontoPendientes = $this->montoPendientes;

            // Limpiar errores
            $this->resetErrorBag();

            $this->emit('pendienteActualizado');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al actualizar el pendiente: ' . $e->getMessage());
        }
    }

    /**
     * El método Render carga los datos necesarios.
     */
    public function render()
    {
        $dependencias = Dependencia::orderBy('dependencia')->get();

        return view('livewire.tesoreria.caja-chica.pendiente.modal-editar-detalle', [
            'dependencias' => $dependencias
        ]);
    }
}
