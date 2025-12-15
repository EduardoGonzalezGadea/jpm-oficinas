<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorMiddleware
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
        $user = Auth::user();

        // Si el usuario está autenticado y tiene 2FA habilitado
        if ($user && $user->two_factor_secret) {
            // Verificar si ya completó el 2FA en esta sesión
            if (!$request->session()->has('auth.2fa.verified')) {

                // Si la ruta es la de verificación o logout, permitir
                if (
                    $request->routeIs('two-factor.login') ||
                    $request->routeIs('two-factor.verify') ||
                    $request->routeIs('logout')
                ) {
                    return $next($request);
                }

                // Si no, redirigir al desafío
                return redirect()->route('two-factor.login');
            }
        }

        return $next($request);
    }
}
