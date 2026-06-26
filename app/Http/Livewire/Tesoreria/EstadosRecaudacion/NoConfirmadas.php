<?php

namespace App\Http\Livewire\Tesoreria\EstadosRecaudacion;

use App\Models\Tesoreria\TesPlanillaEr;
use Livewire\Component;
use Livewire\WithPagination;

class NoConfirmadas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $query = TesPlanillaEr::with(['tipo', 'dependencia', 'items'])
            ->where('confirmada', false)
            ->orderBy('fecha', 'desc')->orderBy('id', 'desc');

        $todasPlanillas = $query->get();

        $dailyTotals = [];
        $lastPerDate = [];
        foreach ($todasPlanillas as $p) {
            $fechaKey = $p->fecha ? $p->fecha->format('Y-m-d') : '0000-00-00';
            $monto = $p->items->sum('importe');
            $dailyTotals[$fechaKey] = ($dailyTotals[$fechaKey] ?? 0) + $monto;
            $lastPerDate[$fechaKey] = $p->id;
        }

        $planillas = $query->paginate(20);

        $grupos = [];
        foreach ($planillas as $p) {
            $fechaKey = $p->fecha ? $p->fecha->format('Y-m-d') : '0000-00-00';
            $fechaDisplay = $p->fecha ? $p->fecha->format('d/m/Y') : 'Sin fecha';
            if (!isset($grupos[$fechaKey])) {
                $grupos[$fechaKey] = [
                    'fecha_display' => $fechaDisplay,
                    'total_dia' => $dailyTotals[$fechaKey] ?? 0,
                    'ultimo_id' => $lastPerDate[$fechaKey] ?? null,
                    'mostrar_total' => false,
                    'planillas' => [],
                ];
            }
            $grupos[$fechaKey]['planillas'][] = $p;
        }

        foreach ($grupos as $fechaKey => &$grupo) {
            $lastInPage = $grupo['planillas'][count($grupo['planillas']) - 1] ?? null;
            $grupo['mostrar_total'] = $lastInPage && $lastInPage->id === $grupo['ultimo_id'];
        }
        unset($grupo);

        return view('livewire.tesoreria.estados-recaudacion.no-confirmadas', compact('planillas', 'grupos'))
            ->extends('layouts.app')
            ->section('content');
    }
}
