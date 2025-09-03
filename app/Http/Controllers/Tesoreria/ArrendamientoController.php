<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArrendamientoController extends Controller
{
    public function index()
    {
        return view('tesoreria.arrendamientos.index');
    }
}
