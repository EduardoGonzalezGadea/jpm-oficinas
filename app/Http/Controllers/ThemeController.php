<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends Controller
{
    public function switchTheme(Request $request): Response
    {
        $request->validate([
            'theme' => 'required|string|in:default,cerulean,litera,cyborg,darkly,cosmo,material',
        ]);

        $theme = $request->input('theme');
        $themePath = '';

        // Mapeo de temas
        if ($theme === 'cerulean') {
            $themePath = 'libs/bootswatch@4.6.2/dist/cerulean/bootstrap.min.css';
        } elseif ($theme === 'cosmo') {
            $themePath = 'libs/bootswatch@4.6.2/dist/cosmo/bootstrap.min.css';
        } elseif ($theme === 'litera') {
            $themePath = 'libs/bootswatch@4.6.2/dist/litera/bootstrap.min.css';
        } elseif ($theme === 'cyborg') {
            $themePath = 'libs/bootswatch@4.6.2/dist/cyborg/bootstrap.min.css';
        } elseif ($theme === 'darkly') {
            $themePath = 'libs/bootswatch@4.6.2/dist/darkly/bootstrap.min.css';
        } elseif ($theme === 'material') {
            $themePath = 'libs/bootswatch@4.6.2/dist/materia/bootstrap.min.css';
        } elseif ($theme === 'default') {
            $themePath = 'libs/bootstrap-4.6.2-dist/css/bootstrap.min.css';
        }

        // El cache busting sigue siendo una buena práctica.
        $themePathWithBusting = $themePath ? $themePath . '?v=' . time() : '';

        // Guardar en el perfil del usuario si está autenticado
        $user = auth()->user();

        // Si no hay usuario en la sesión web, intentar recuperarlo del token JWT (cookie)
        if (!$user) {
            $token = $request->cookie('jwt_token');
            if ($token) {
                try {
                    $user = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token)->toUser();
                } catch (\Exception $e) {
                    // Ignorar errores de token aquí
                }
            }
        }

        if ($user instanceof \App\Models\User) {
            $user->update(['theme' => $theme]);

            // Si el usuario fue recuperado vía JWT pero no estaba en la sesión web, 
            // lo logueamos en la sesión web para sincronizar ambos estados.
            if (!auth()->check()) {
                auth()->login($user);
            }
        }

        // Definimos la duración de la cookie en minutos (1 año = 525600 minutos)
        $cookieDuration = 525600;

        // Creamos las cookies
        $themeNameCookie = Cookie::make('theme_name', $theme, $cookieDuration);
        $themePathCookie = Cookie::make('theme_path', $themePathWithBusting, $cookieDuration);

        // Si es una petición AJAX (desde JS fetch), devolvemos JSON
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success'])->withCookie($themeNameCookie)->withCookie($themePathCookie);
        }

        // Redirigimos de vuelta, adjuntando las cookies a la respuesta.
        return back()->withCookies([$themeNameCookie, $themePathCookie]);
    }
}
