<?php
// app/Http/Livewire/Tesoreria/Cheque/PlanillasIndex.php
namespace App\Http\Livewire\Tesoreria\Cheque;

use App\Models\Tesoreria\PlanillaCheque;
use Livewire\Component;

class PlanillasIndex extends Component
{
    public $planillas;
    public $añoSeleccionado;

    public function mount()
    {
        $this->añoSeleccionado = date('Y'); // Año actual por defecto
        $this->loadPlanillas();
    }

    public function loadPlanillas()
    {
        $query = PlanillaCheque::with('cheques.cuentaBancaria.banco')
            ->orderBy('created_at', 'desc');

        // Filtrar por año si está seleccionado
        if ($this->añoSeleccionado) {
            $query->whereYear('created_at', $this->añoSeleccionado);
        }

        $this->planillas = $query->get()->toArray();
    }

    public function updatedAñoSeleccionado()
    {
        $this->loadPlanillas();
    }

    public function refreshPlanillas()
    {
        $this->loadPlanillas();
    }

    public function getAñosDisponibles()
    {
        $años = PlanillaCheque::selectRaw('YEAR(created_at) as año')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año')
            ->toArray();

        // Agregar el año actual si no existe
        $añoActual = date('Y');
        if (!in_array($añoActual, $años)) {
            $años[] = $añoActual;
            sort($años, SORT_NUMERIC);
        }

        return $años;
    }

    protected $listeners = [
        'planillaCreada' => 'refreshPlanillas',
        'planillaAnulada' => 'refreshPlanillas',
        'chequesActualizados' => 'refreshPlanillas'
    ];

    public function render()
    {
        return view('livewire.tesoreria.cheque.planillas-index');
    }
}
