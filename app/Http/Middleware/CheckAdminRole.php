<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar si el usuario tiene el rol de administrador
        if (!auth()->check() || !auth()->user()->hasRole('administrador')) {
            abort(403, 'Acceso denegado. Solo los administradores pueden acceder al módulo de Tesorería mientras está en desarrollo.');
        }

        return $next($request);
    }
}
