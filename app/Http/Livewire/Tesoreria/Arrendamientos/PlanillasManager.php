<?php

namespace App\Http\Livewire\Tesoreria\Arrendamientos;

use Livewire\Component;
use App\Models\Tesoreria\Planilla;
use App\Models\Tesoreria\Arrendamiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PlanillasManager extends Component
{
    public $planillas;
    public $arrendamientosDisponibles;
    public $mes, $year;

    protected $listeners = ['planillaCreated' => 'loadPlanillas', 'arrendamientoStatusUpdated' => 'loadArrendamientosDisponibles', 'deletePlanilla'];

    public function mount($mes = null, $year = null)
    {
        $this->mes = $mes ?? date('m');
        $this->year = $year ?? date('Y');
        $this->loadArrendamientosDisponibles();
    }

    public function loadPlanillas()
    {
        $this->planillas = Planilla::whereYear('fecha_creacion', $this->year)
            ->whereMonth('fecha_creacion', $this->mes)
            ->orderBy('numero', 'asc')->get();
    }

    public function loadArrendamientosDisponibles()
    {
        $this->arrendamientosDisponibles = Arrendamiento::confirmedAndNotInPlanilla()->get();
    }

    public function createPlanilla()
    {
        // Get confirmed and un-planillized arrendamientos
        $arrendamientosToInclude = Arrendamiento::confirmedAndNotInPlanilla()->get();

        if ($arrendamientosToInclude->isEmpty()) {
            $this->dispatchBrowserEvent('alert', ['type' => 'warning', 'message' => 'No hay arrendamientos confirmados disponibles para crear una planilla.']);
            return;
        }

        // Generate planilla number
        $today = Carbon::today();
        $countToday = Planilla::whereDate('fecha_creacion', $today)->withTrashed()->count();
        $newNumber = $countToday + 1;
        $planillaNumero = $today->format('d-m-Y') . '-' . $newNumber;

        // Create the Planilla record
        if (!Auth::check()) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Debe iniciar sesión para crear una planilla.']);
            return;
        }

        $planilla = Planilla::create([
            'numero' => $planillaNumero,
            'fecha_creacion' => $today,
            'user_id' => Auth::id(),
        ]);

        // Associate arrendamientos with the new planilla
        foreach ($arrendamientosToInclude as $arrendamiento) {
            $arrendamiento->update(['planilla_id' => $planilla->id]);
        }

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Planilla ' . $planillaNumero . ' creada exitosamente.']);
        $this->loadPlanillas();
        $this->loadArrendamientosDisponibles();

        // Emit event to refresh the main arrendamientos list
        $this->emit('planillaCreated');
    }

    public function printPlanilla($planillaId)
    {
        $url = route('tesoreria.arrendamientos.planillas-print', $planillaId);
        $this->dispatchBrowserEvent('openNewTab', ['url' => $url]);
    }

    public function deletePlanilla($planillaId)
    {
        $planilla = Planilla::withTrashed()->find($planillaId); // Use withTrashed to find soft deleted if needed, though for deletion it's usually find()
        if (!$planilla) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Planilla no encontrada.']);
            return;
        }

        // Disassociate arrendamientos
        Arrendamiento::where('planilla_id', $planilla->id)->update(['planilla_id' => null]);

        // Delete the planilla (soft delete)
        $planilla->delete();

        $this->dispatchBrowserEvent('alert', ['type' => 'success', 'message' => 'Planilla ' . $planilla->numero . ' eliminada exitosamente. Los arrendamientos asociados están nuevamente disponibles.']);
        $this->loadPlanillas();
        $this->loadArrendamientosDisponibles();

        // Emit event to refresh the main arrendamientos list
        $this->emit('planillaDeleted');
    }

    public function render()
    {
        $this->loadPlanillas();
        return view('livewire.tesoreria.arrendamientos.planillas-manager', [
            'planillas' => $this->planillas,
            'arrendamientosDisponiblesCount' => $this->arrendamientosDisponibles->count(),
        ]);
    }
}
