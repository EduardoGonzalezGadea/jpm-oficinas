<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Modulo;
use App\Services\AlertService;

class PanelController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

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

        // Obtener alertas centralizadas con cache
        $alertas = $this->alertService->getAllAlerts();

        return view('panel.index', compact('usuario', 'estadisticas', 'alertas'));
    }
}