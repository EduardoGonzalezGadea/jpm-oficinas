<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class JWTVerify
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario ya está autenticado a través de la sesión web, continuamos.
        // Esto permite que el sistema de sesión normal de Laravel y Spatie funcionen como se espera.
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        // Si no hay sesión web, intentar autenticar con el token JWT (como un "recuérdame").
        try {
            $token = $request->cookie('jwt_token');

            if (!$token) {
                return $this->unauthorized($request, 'Se requiere iniciar sesión.');
            }

            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return $this->unauthorized($request, 'Usuario no encontrado desde el token.');
            }

            // Iniciar la sesión web para el usuario.
            Auth::guard('web')->login($user);

            // Explicitly set the user for the default guard (which Spatie uses)
            Auth::setUser($user);

            // Cargar los permisos para esta nueva sesión.
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $user->load(['roles.permissions', 'permissions']);

        } catch (TokenExpiredException $e) {
            return $this->unauthorized($request, 'La sesión ha expirado. Por favor, inicie sesión de nuevo.');
        } catch (TokenInvalidException | JWTException $e) {
            return $this->unauthorized($request, 'La sesión es inválida. Por favor, inicie sesión de nuevo.');
        } catch (\Exception $e) {
            return $this->unauthorized($request, 'Error de autenticación.');
        }

        return $next($request);
    }

    /**
     * Maneja el acceso no autorizado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $message
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function unauthorized(Request $request, $message = 'No autorizado')
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['error' => $message], 401);
        }

        return redirect()->route('login')
            ->with('error', $message)
            ->withoutCookie('jwt_token');
    }
}
