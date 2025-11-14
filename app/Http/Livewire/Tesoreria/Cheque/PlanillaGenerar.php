<?php
// app/Http/Livewire/Tesoreria/Cheque/PlanillaGenerar.php
namespace App\Http\Livewire\Tesoreria\Cheque;

use App\Models\Tesoreria\Cheque;
use App\Models\Tesoreria\PlanillaCheque;
use Livewire\Component;

class PlanillaGenerar extends Component
{
    public $chequesSeleccionados = [];
    public $cheques = [];

    public function getTotalSeleccionadoProperty()
    {
        if (empty($this->chequesSeleccionados)) {
            return 0;
        }

        $total = 0;
        foreach ($this->chequesSeleccionados as $chequeId) {
            $cheque = collect($this->cheques)->firstWhere('id', $chequeId);
            if ($cheque && isset($cheque['monto'])) {
                $total += $cheque['monto'];
            }
        }

        return $total;
    }

    public function mount()
    {
        $this->loadCheques();
    }

    public function loadCheques()
    {
        $this->cheques = Cheque::sinAnulacionesPlanilla()
            ->where('estado', 'emitido')
            ->whereNull('planilla_id')
            ->with('cuentaBancaria.banco')
            ->get()
            ->toArray();
    }
    public function selectAll()
    {
        if (count($this->chequesSeleccionados) === count($this->cheques)) {
            $this->chequesSeleccionados = [];
        } else {
            $this->chequesSeleccionados = array_column($this->cheques, 'id');
        }
    }

    public function generar()
    {
        if (empty($this->chequesSeleccionados)) {
            $this->addError('chequesSeleccionados', 'Seleccione al menos un cheque.');
            return;
        }

        $planilla = PlanillaCheque::create([
            'numero_planilla' => 'PL-' . now()->format('Y') . '-' . str_pad(PlanillaCheque::count() + 1, 4, '0', STR_PAD_LEFT),
            'fecha_generacion' => now(),
            'estado' => 'generada',
            'generada_por' => auth()->id(),
        ]);

        foreach ($this->chequesSeleccionados as $id) {
            $cheque = Cheque::find($id);
            $cheque->update(['planilla_id' => $planilla->id, 'estado' => 'en_planilla']);
        }

        session()->flash('success', 'Planilla generada exitosamente!');
        return redirect()->route('tesoreria.cheques.planilla.ver', $planilla->id);
    }

    public function render()
    {
        // Asegurar que cheques esté siempre disponible
        if (!isset($this->cheques)) {
            $this->loadCheques();
        }

        return view('livewire.tesoreria.cheque.planilla-generar');
    }
}
