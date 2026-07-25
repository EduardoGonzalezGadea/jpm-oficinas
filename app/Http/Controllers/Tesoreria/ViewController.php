<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\EventualPlanilla;
use App\Models\Tesoreria\Planilla;
use App\Models\Tesoreria\PrendaPlanilla;

class ViewController extends Controller
{
    public function eventualPlanillaPrint($id)
    {
        $planilla = EventualPlanilla::findOrFail($id);
        return view('tesoreria.eventuales.planillas-print', compact('planilla'));
    }

    public function arrendamientoPlanillaPrint($id)
    {
        $planilla = Planilla::findOrFail($id);
        return view('tesoreria.arrendamientos.planillas-print', compact('planilla'));
    }

    public function prendaPlanillaPrint($id)
    {
        $planilla = PrendaPlanilla::findOrFail($id);
        return view('tesoreria.prendas.planillas-print', compact('planilla'));
    }

    public function imprimirAvanzadoNoImplementado()
    {
        return 'Impresión Avanzada No Implementada aún';
    }
}
