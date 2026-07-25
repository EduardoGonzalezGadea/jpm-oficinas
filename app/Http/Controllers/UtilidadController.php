<?php

namespace App\Http\Controllers;

use App\Services\SincronizacionHoraService;
use App\Services\Tesoreria\DescargaValoresSoaService;
use App\Services\ValorUrService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UtilidadController extends Controller
{
    public function getValorUr(ValorUrService $valorUrService)
    {
        $resultado = $valorUrService->obtener();
        return response()->json($resultado);
    }

    public function getHoraUruguay(SincronizacionHoraService $sincronizacionHoraService)
    {
        $resultado = $sincronizacionHoraService->obtener();
        return response()->json($resultado);
    }

    public function actualizarValoresSoa(DescargaValoresSoaService $descargaValoresSoaService)
    {
        $resultado = $descargaValoresSoaService->descargarYActualizar();

        $statusCode = $resultado['success'] ? 200 : 500;
        return response()->json($resultado, $statusCode);
    }

    public function fallback()
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Ruta no encontrada'], 404);
        }

        return auth()->check()
            ? response()->view('errors.404', [], 404)
            : redirect()->route('login');
    }
}
