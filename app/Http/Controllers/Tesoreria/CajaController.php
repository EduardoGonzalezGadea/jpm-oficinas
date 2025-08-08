<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index()
    {
        return view('tesoreria.cajas.index');
    }

    public function aperturaCierre()
    {
        return view('tesoreria.cajas.apertura-cierre');
    }

    public function movimientos()
    {
        return view('tesoreria.cajas.movimientos');
    }

    public function arqueo()
    {
        return view('tesoreria.cajas.arqueo');
    }

    public function denominaciones()
    {
        return view('tesoreria.cajas.denominaciones');
    }
}
