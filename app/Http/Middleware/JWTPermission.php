<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JWTPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permissions
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permissions)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorized($request, 'Usuario no autenticado');
        }

        // Convertir permisos en array si es una cadena separada por |
        $permissionsArray = explode('|', $permissions);

        // Verificar si el usuario tiene alguno de los permisos requeridos
        $hasPermission = false;
        foreach ($permissionsArray as $permission) {
            if ($user->hasPermissionTo(trim($permission))) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return $this->unauthorized($request, 'No tienes el permiso requerido');
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    private function unauthorized(Request $request, $message = 'No autorizado')
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => $message,
                'code' => 403
            ], 403);
        }

        return redirect()->back()->with('error', $message);
    }
}
