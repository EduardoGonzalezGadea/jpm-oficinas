<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\DepositoVehiculoPlanilla;
use Illuminate\Http\Request;

class DepositoVehiculosController extends Controller
{
    /**
     * Vista principal de depósitos de vehículos
     */
    public function index()
    {
        return view('tesoreria.deposito-vehiculos.index');
    }

    /**
     * Listado de planillas
     */
    public function planillasIndex()
    {
        return view('tesoreria.deposito-vehiculos.planillas');
    }

    /**
     * Ver detalle de una planilla
     */
    public function planillaShow($id)
    {
        $planilla = DepositoVehiculoPlanilla::with(['depositos.medioPago', 'anuladaPor'])
            ->findOrFail($id);
        
        return view('tesoreria.deposito-vehiculos.planilla-show', compact('planilla'));
    }

    /**
     * Vista de impresión de planilla
     */
    public function planillaPrint($id)
    {
        $planilla = DepositoVehiculoPlanilla::with(['depositos.medioPago'])
            ->findOrFail($id);
        
        return view('tesoreria.deposito-vehiculos.planilla-print', compact('planilla'));
    }
}
