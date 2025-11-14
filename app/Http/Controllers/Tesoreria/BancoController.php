<?php
// app/Http/Controllers/Tesoreria/BancoController.php
namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Models\Tesoreria\Banco;
use Illuminate\Http\Request;

class BancoController extends Controller
{
    public function index()
    {
        return view('tesoreria.bancos.index');
    }
}
