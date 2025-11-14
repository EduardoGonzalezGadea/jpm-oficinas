<?php
// app/Http/Livewire/Tesoreria/Cheque/PlanillaVer.php
namespace App\Http\Livewire\Tesoreria\Cheque;

use App\Models\Tesoreria\PlanillaCheque;
use Livewire\Component;

class PlanillaVer extends Component
{
    public $planilla;

    public function mount($id)
    {
        $this->planilla = PlanillaCheque::with('cheques.cuentaBancaria.banco')->findOrFail($id);
    }

    public function anularPlanilla()
    {
        // Verificar que la planilla no esté anulada
        if ($this->planilla->estado === 'anulada') {
            $this->dispatchBrowserEvent('swal', [
                'title' => 'Planilla ya anulada',
                'type' => 'warning'
            ]);
            return;
        }

        // Mostrar loader
        $this->dispatchBrowserEvent('show-global-spinner');

        // Mostrar modal personalizado para ingresar motivo
        $this->dispatchBrowserEvent('swal:confirm-with-input', [
            'title' => '¿Anular Planilla?',
            'text' => 'Esta acción liberará todos los cheques de la planilla y no se puede deshacer.',
            'input' => 'text',
            'inputLabel' => 'Motivo de anulación (obligatorio)',
            'inputPlaceholder' => 'Ingrese el motivo por el cual se anula la planilla...',
            'inputValidator' => 'function(value) { return !value ? "El motivo es obligatorio" : null; }',
            'method' => 'confirmarAnularPlanilla',
            'componentId' => $this->id,
            'confirmButtonText' => 'Sí, anular planilla',
            'cancelButtonText' => 'Cancelar',
            'inputAttributes' => [
                'required' => true,
                'minlength' => 10
            ]
        ]);

        // Ocultar loader cuando se cancele
        $this->dispatchBrowserEvent('hide-global-spinner');
    }

    public function confirmarAnularPlanilla($motivo = null)
    {
        // Mostrar loader
        $this->dispatchBrowserEvent('show-global-spinner');

        try {
            // Validar que se proporcione un motivo
            if (!$motivo || trim($motivo) === '') {
                $this->dispatchBrowserEvent('swal', [
                    'title' => 'Motivo requerido',
                    'text' => 'Debe proporcionar un motivo para anular la planilla.',
                    'type' => 'error'
                ]);
                $this->dispatchBrowserEvent('hide-global-spinner');
                return;
            }

            // Crear duplicados de los cheques asociados a la planilla
            foreach ($this->planilla->cheques as $cheque) {
                $duplicado = $cheque->replicate();
                $duplicado->fecha_planilla_anulada = now()->toDateString();
                $duplicado->planilla_anulada_por = auth()->id();
                $duplicado->motivo_anulacion = $motivo;
                $duplicado->save();
            }

            // Actualizar los cheques originales: cambiar estado a emitido y quitar planilla_id
            foreach ($this->planilla->cheques as $cheque) {
                $cheque->update([
                    'estado' => 'emitido',
                    'planilla_id' => null,
                    'updated_by' => auth()->id()
                ]);
            }

            // Marcar la planilla como anulada con el motivo
            $this->planilla->update([
                'estado' => 'anulada',
                'anulado_por' => auth()->id(),
                'fecha_anulacion' => now(),
                'motivo_anulacion' => $motivo
            ]);

            $this->dispatchBrowserEvent('swal:toast', [
                'text' => 'Planilla anulada correctamente',
                'type' => 'success'
            ]);

            // Emitir evento para actualizar el listado de planillas
            $this->emit('planillaAnulada');

            // Emitir evento para actualizar el listado de cheques emitidos
            $this->emit('chequesActualizados');

            // Recargar la planilla para mostrar las copias (incluyendo los duplicados)
            $this->planilla = \App\Models\Tesoreria\PlanillaCheque::with('cheques.cuentaBancaria.banco')->findOrFail($this->planilla->id);

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('swal', [
                'title' => 'Error',
                'text' => 'Ocurrió un error al anular la planilla.',
                'type' => 'error'
            ]);
        } finally {
            // Ocultar loader
            $this->dispatchBrowserEvent('hide-global-spinner');
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cheque.planilla-ver', [
            'planilla' => $this->planilla
        ]);
    }
}
