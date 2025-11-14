<?php

namespace App\Http\Livewire\Tesoreria\CajaChica\Pendiente;

use Livewire\Component;
use App\Models\Tesoreria\Pendiente;
use App\Models\Tesoreria\Dependencia;
use Illuminate\Support\Facades\DB;

class EditarPendiente extends Component
{
    // --- Tus propiedades públicas existentes (¡perfectas!) ---
    public $idPendiente;
    public $relCajaChica;
    public $nroPendiente;
    public $fechaPendientes;
    public $relDependencia;
    public $montoPendientes;

    protected $listeners = ['movimientoActualizado' => 'render'];

    // Almacenar los valores originales para poder resetear
    public $originalNroPendiente;
    public $originalFechaPendientes;
    public $originalRelDependencia;
    public $originalMontoPendientes;

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
     * El método Mount no necesita cambios.
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
     * El método Render no necesita cambios.
     */
    public function render()
    {
        $pendiente = Pendiente::with('cajaChica', 'dependencia', 'movimientos')->findOrFail($this->idPendiente);
        $dependencias = Dependencia::orderBy('dependencia')->get();

        return view('livewire.tesoreria.caja-chica.pendiente.editar-pendiente', [
            'pendiente' => $pendiente,
            'dependencias' => $dependencias
        ]);
    }

    // Nuevo método para resetear el formulario
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
     * El método de guardado
     */
    public function guardarCambios()
    {
        $duplicado = Pendiente::where('pendiente', $this->nroPendiente)
            ->where('relCajaChica', $this->relCajaChica)
            ->where('idPendientes', '!=', $this->idPendiente)
            ->exists();

        if ($duplicado) {
            // Si ya existe un pendiente con el mismo número, mostramos un mensaje de error
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
            session()->flash('success', 'Pendiente actualizado con éxito');
            return redirect()->route('tesoreria.caja-chica.pendientes.editar', ['id' => $this->idPendiente]);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al actualizar el pendiente: ' . $e->getMessage());
            return;
        }
    }
}
