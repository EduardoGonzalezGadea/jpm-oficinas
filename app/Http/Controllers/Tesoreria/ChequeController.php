<?php
// app/Http/Controllers/Tesoreria/Cheque/ChequeController.php
namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\PlanillaCheque;

class ChequeController extends Controller
{
    public function libreta()
    {
        return view('tesoreria.cheques.libreta');
    }

    public function emitir()
    {
        return view('tesoreria.cheques.emitir');
    }

    public function planillaGenerar()
    {
        return view('tesoreria.cheques.planilla-generar');
    }

    public function planillaVer($id)
    {
        return view('tesoreria.cheques.planilla-ver', compact('id'));
    }

    public function reportes()
    {
        return view('tesoreria.cheques.reportes');
    }

    public function imprimirPlanilla($id)
    {
        $planilla = PlanillaCheque::with('cheques.cuentaBancaria.banco')->findOrFail($id);
        $reportTitle = 'PLANILLA DE CHEQUES N° ' . substr($planilla->numero_planilla, strrpos($planilla->numero_planilla, '-') + 1) . '/' . $planilla->created_at->format('Y');

        return view('tesoreria.cheques.planilla-imprimir', compact('planilla', 'reportTitle'));
    }
}
