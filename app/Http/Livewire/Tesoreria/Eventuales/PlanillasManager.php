<?php

namespace App\Http\Livewire\Tesoreria\Eventuales;

use Livewire\Component;
use App\Models\Tesoreria\EventualPlanilla as Planilla;
use App\Models\Tesoreria\Eventual;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PlanillasManager extends Component
{
    public $planillas;
    public $eventualesDisponibles;
    public $mes, $year;

    protected $listeners = ['planillaCreated' => 'refreshPlanillas', 'eventualStatusUpdated' => 'loadEventualesDisponibles', 'deletePlanilla'];

    public function mount($mes = null, $year = null)
    {
        $this->mes = $mes ?? date('m');
        $this->year = $year ?? date('Y');
        $this->loadPlanillas();
        $this->loadEventualesDisponibles();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['mes', 'year'])) {
            $this->loadPlanillas();
        }
    }

    public function refreshPlanillas()
    {
        $this->loadPlanillas();
    }

    public function loadPlanillas()
    {
        $this->planillas = Planilla::whereYear('fecha_creacion', $this->year)
            ->whereMonth('fecha_creacion', $this->mes)
            ->orderBy('numero', 'asc')
            ->get();
    }

    public function loadEventualesDisponibles()
    {
        $this->eventualesDisponibles = Eventual::confirmedAndNotInPlanilla()->get();
    }

    public function createPlanilla()
    {
        $eventualesToInclude = Eventual::confirmedAndNotInPlanilla()->get();

        if ($eventualesToInclude->isEmpty()) {
            $this->dispatchBrowserEvent('alert', ['type' => 'warning', 'message' => 'No hay eventuales confirmados disponibles para crear una planilla.']);
            return;
        }

        $today = Carbon::today();
        $countToday = Planilla::whereDate('fecha_creacion', $today)->withTrashed()->count();
        $newNumber = $countToday + 1;
        $planillaNumero = $today->format('d-m-Y') . '-' . $newNumber;

        if (!Auth::check()) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Debe iniciar sesión para crear una planilla.']);
            return;
        }

        $planilla = Planilla::create([
            'numero' => $planillaNumero,
            'fecha_creacion' => $today,
            'user_id' => Auth::id(),
        ]);

        foreach ($eventualesToInclude as $eventual) {
            $eventual->update(['planilla_id' => $planilla->id]);
        }

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Planilla ' . $planillaNumero . ' creada exitosamente.']);
        $this->loadPlanillas();
        $this->loadEventualesDisponibles();

        // Emit event to refresh the main eventuals list
        $this->emit('planillaCreated');
    }

    public function printPlanilla($planillaId)
    {
        $url = route('tesoreria.eventuales.planillas-print', $planillaId);
        $this->dispatchBrowserEvent('openNewTab', ['url' => $url]);
    }

    public function deletePlanilla($planillaId)
    {
        $planilla = Planilla::withTrashed()->find($planillaId);
        if (!$planilla) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Planilla no encontrada.']);
            return;
        }

        Eventual::where('planilla_id', $planilla->id)->update(['planilla_id' => null]);

        $planilla->delete();

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Planilla ' . $planilla->numero . ' eliminada exitosamente. Los eventuales asociados están nuevamente disponibles.']);
        $this->loadPlanillas();
        $this->loadEventualesDisponibles();

        // Emit event to refresh the main eventuals list
        $this->emit('planillaDeleted');
    }

    public function render()
    {
        $this->loadPlanillas();
        return view('livewire.tesoreria.eventuales.planillas-manager', [
            'planillas' => $this->planillas,
            'eventualesDisponiblesCount' => $this->eventualesDisponibles->count(),
        ]);
    }
}
