<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TesoreriaController extends Controller
{
    public function index()
    {
        $this->authorize('gestionar_tesoreria');

        return view('tesoreria.index');
    }
}