<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JWTRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorized($request, 'Usuario no autenticado');
        }

        // Convertir roles en array si es una cadena separada por |
        $rolesArray = explode('|', $roles);

        // Verificar si el usuario tiene alguno de los roles requeridos
        $hasRole = false;
        foreach ($rolesArray as $role) {
            if ($user->hasRole(trim($role))) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            return $this->unauthorized($request, 'No tienes permisos suficientes');
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
