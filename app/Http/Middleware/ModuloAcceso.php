<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\ModuleRegistry;

class ModuloAcceso
{
    public function handle(Request $request, Closure $next, string $modulo, ?string $nivelMinimo = null)
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado');
        }

        if ($user->esAdministrador()) {
            return $next($request);
        }

        $moduloUsuario = $user->moduloClave();

        abort_if($moduloUsuario !== $modulo, 403, "No pertenece al módulo {$modulo}");

        if ($nivelMinimo) {
            $nivelUsuario = $user->nivelActual();
            $jerarquiaUsuario = ModuleRegistry::nivelJerarquia($nivelUsuario);
            $jerarquiaMinima = ModuleRegistry::nivelJerarquia($nivelMinimo);

            abort_if($jerarquiaUsuario < $jerarquiaMinima, 403, "Se requiere nivel {$nivelMinimo} o superior");
        }

        return $next($request);
    }
}
