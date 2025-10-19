<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContabilidadController extends Controller
{
    public function index()
    {
        $this->authorize('gestionar_contabilidad');

        return view('contabilidad.index');
    }
}
