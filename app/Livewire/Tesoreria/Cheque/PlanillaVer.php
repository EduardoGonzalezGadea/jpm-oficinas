<?php
// app/Http/Livewire/Tesoreria/Cheque/PlanillaVer.php
namespace App\Livewire\Tesoreria\Cheque;

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
            $this->dispatch('swal:alert', type: 'warning', title: 'Planilla ya anulada', text: 'Esta planilla ya se encuentra anulada.');
            return;
        }

        // Mostrar modal personalizado para ingresar motivo
        $this->dispatch('swal:confirm-with-input',
            title: '¿Anular Planilla?',
            text: 'Esta acción liberará todos los cheques de la planilla y no se puede deshacer.',
            input: 'text',
            inputLabel: 'Motivo de anulación (obligatorio)',
            inputPlaceholder: 'Ingrese el motivo por el cual se anula la planilla...',
            inputValidator: 'function(value) { return !value ? "El motivo es obligatorio" : null; }',
            method: 'confirmarAnularPlanilla',
            componentId: $this->getId(),
            confirmButtonText: 'Sí, anular planilla',
            cancelButtonText: 'Cancelar',
            inputAttributes: [
                'required' => true,
                'minlength' => 10
            ]
        );
    }

    public function confirmarAnularPlanilla($motivo = null)
    {
        // Mostrar loader
        $this->dispatch('show-global-spinner');

        try {
            // Validar que se proporcione un motivo
            if (!$motivo || trim($motivo) === '') {
                $this->dispatch('swal:alert', type: 'error', title: 'Motivo requerido', text: 'Debe proporcionar un motivo para anular la planilla.');
                $this->dispatch('hide-global-spinner');
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

            $this->dispatch('swal:toast', text: 'Planilla anulada correctamente', type: 'success');

            // Emitir evento para actualizar el listado de planillas
            $this->dispatch('planillaAnulada');

            // Emitir evento para actualizar el listado de cheques emitidos
            $this->dispatch('chequesActualizados');

            // Recargar la planilla para mostrar las copias (incluyendo los duplicados)
            $this->planilla = \App\Models\Tesoreria\PlanillaCheque::with('cheques.cuentaBancaria.banco')->findOrFail($this->planilla->id);

        } catch (\Exception $e) {
            $this->dispatch('swal:alert', type: 'error', title: 'Error', text: 'Ocurrió un error al anular la planilla.');
        } finally {
            // Ocultar loader
            $this->dispatch('hide-global-spinner');
        }
    }

    public function render()
    {
        return view('livewire.tesoreria.cheque.planilla-ver', [
            'planilla' => $this->planilla
        ]);
    }
}
