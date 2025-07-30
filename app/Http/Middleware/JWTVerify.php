<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class JWTVerify
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
        try {
            // Intentar obtener el token del header Authorization
            $token = $request->bearerToken();

            // Si no hay token en el header, intentar obtenerlo de las cookies
            if (!$token) {
                $token = $request->cookie('jwt_token');
            }

            // Si no hay token en ningún lado, intentar obtenerlo del parámetro token
            if (!$token) {
                $token = $request->get('token');
            }

            if (!$token) {
                return $this->unauthorized($request, 'La sesión se ha cerrado');
            }

            // Establecer el token y autenticar
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            if (!$user) {
                return $this->unauthorized($request, 'Usuario no encontrado');
            }

            // Añadir el usuario autenticado al request
            $request->merge(['auth_user' => $user]);
            auth()->setUser($user);
        } catch (TokenExpiredException $e) {
            return $this->unauthorized($request, 'La sesión se ha cerrado');
        } catch (TokenInvalidException $e) {
            return $this->unauthorized($request, 'Token inválido');
        } catch (JWTException $e) {
            return $this->unauthorized($request, 'Error en el token JWT');
        } catch (\Exception $e) {
            return $this->unauthorized($request, 'Error de autenticación');
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    private function unauthorized(Request $request, $message = 'No autorizado')
    {
        // Si es una petición AJAX o API
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => $message,
                'code' => 401
            ], 401);
        }

        // Para peticiones web, redirigir al login
        return redirect()->route('login')->with('error', $message);
    }
}
