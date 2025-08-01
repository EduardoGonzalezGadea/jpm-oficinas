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
            'usuarios_contabilidad' => User::activos()->whereHas('modulo', function ($q) {
                $q->where('nombre', 'Contabilidad');
            })->count(),
        ];

        return view('panel.index', compact('usuario', 'estadisticas'));
    }

    public function debugPermissions()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Forzar la recarga de permisos para asegurar que estén actualizados
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $user->load(['roles.permissions', 'permissions']);

        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}