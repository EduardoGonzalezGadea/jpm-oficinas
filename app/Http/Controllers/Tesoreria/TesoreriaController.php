<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TesoreriaController extends Controller
{
    public function index()
    {
        $this->authorize('gestionar_tesoreria');

        return view('tesoreria.index');
    }

    public function cajaDiaria($tab = 'resumen')
    {
        $this->authorize('operador_tesoreria');
        return view('tesoreria.caja-diaria.index', ['tab' => $tab]);
    }
}
