<?php

namespace App\Http\Controllers\Tesoreria\CajaDiaria;

use App\Http\Controllers\Controller;

class CajaDiariaController extends Controller
{
    /**
     * Muestra la vista principal de Caja Diaria.
     */
    public function index()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-diaria.index');
    }

    /**
     * Muestra la vista de Apertura / Cierre de Caja.
     */
    public function aperturaCierre()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-diaria.apertura-cierre');
    }

    /**
     * Muestra la vista de Cobros.
     */
    public function cobrar()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-diaria.cobrar');
    }

    /**
     * Muestra la vista de Pagos.
     */
    public function pagar()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-diaria.pagar');
    }

    /**
     * Muestra la vista de Arqueo.
     */
    public function arqueo()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-diaria.arqueo');
    }

    /**
     * Muestra la vista de Movimientos.
     */
    public function movimientos()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-diaria.movimientos');
    }
}