<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContabilidadController extends Controller
{
    public function index()
    {
        $this->authorize('gestionar_contabilidad');

        return view('contabilidad.index');
    }
}