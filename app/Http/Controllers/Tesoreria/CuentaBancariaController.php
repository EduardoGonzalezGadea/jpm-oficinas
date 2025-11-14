<?php
// app/Http/Controllers/Tesoreria/CuentaBancariaController.php
namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;

class CuentaBancariaController extends Controller
{
    public function index()
    {
        return view('tesoreria.cuentas-bancarias.index');
    }
}
