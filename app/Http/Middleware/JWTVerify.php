<?php

namespace App\Http\Middleware;

use App\Http\Responses\SessionExpiredResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class JWTVerify
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        try {
            $token = $request->cookie('jwt_token');

            if (!$token) {
                return $this->unauthorized($request, 'Se requiere iniciar sesión.');
            }

            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return $this->unauthorized($request, 'Usuario no encontrado desde el token.');
            }

            Auth::guard('web')->login($user);
            Auth::setUser($user);

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

    private function unauthorized(Request $request, $message = 'No autorizado')
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'error' => $message,
                'message' => $message,
                'redirect' => route('login'),
            ], 401)->withoutCookie('jwt_token');
        }

        return SessionExpiredResponse::make($request, $message);
    }
}