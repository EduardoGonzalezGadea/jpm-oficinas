<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class ThemeController extends Controller
{
    public function switchTheme(Request $request): RedirectResponse
    {
        $request->validate([
            'theme' => 'required|string|in:default,cerulean,litera,cyborg,darkly,cosmo',
        ]);

        $theme = $request->input('theme');
        $themePath = '';

        // Mapeo de temas (tu lógica está bien, la mantenemos)
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
        }
        // Para 'default', la ruta es una cadena vacía.

        // El cache busting sigue siendo una buena práctica.
        $themePathWithBusting = $themePath ? $themePath . '?v=' . time() : '';

        // Definimos la duración de la cookie en minutos (1 año = 525600 minutos)
        $cookieDuration = 525600;

        // Creamos las cookies
        $themeNameCookie = Cookie::make('theme_name', $theme, $cookieDuration);
        $themePathCookie = Cookie::make('theme_path', $themePathWithBusting, $cookieDuration);

        // Redirigimos de vuelta, adjuntando las cookies a la respuesta.
        return back()->withCookies([$themeNameCookie, $themePathCookie]);
    }
}
