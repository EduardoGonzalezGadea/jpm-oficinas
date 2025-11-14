<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Modulo;

class PanelController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();

        // Estadísticas para el panel
        $estadisticas = [
            'total_usuarios' => User::activos()->count(),
            'total_modulos' => Modulo::activos()->count(),
            'usuarios_tesoreria' => User::activos()->whereHas('modulo', function ($q) {
                $q->where('nombre', 'Tesorería');
            })->count(),
        ];

        return view('panel.index', compact('usuario', 'estadisticas'));
    }
}