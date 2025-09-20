<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArmasController extends Controller
{
    public function index()
    {
        return view('tesoreria.armas.index');
    }

    public function porte()
    {
        return view('tesoreria.armas.porte');
    }

    public function tenencia()
    {
        return view('tesoreria.armas.tenencia');
    }
}
