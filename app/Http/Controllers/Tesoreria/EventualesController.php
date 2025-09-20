<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EventualesController extends Controller
{
    public function index()
    {
        return view('tesoreria.eventuales.index');
    }
}
