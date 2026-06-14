<?php

namespace App\Http\Controllers;

use App\Services\SincronizacionHoraService;
use App\Services\Tesoreria\DescargaValoresSoaService;
use App\Services\ValorUrService;
use Illuminate\Support\Facades\Log;

class UtilidadController extends Controller
{
    /**
     * Obtiene el valor de la Unidad Reajustable (UR) desde el sitio del BPS.
     */
    public function getValorUr(ValorUrService $valorUrService)
    {
        return response()->json($valorUrService->obtener());
    }

    /**
     * Obtiene la hora actual sincronizada de Uruguay desde APIs públicas.
     */
    public function getHoraUruguay(SincronizacionHoraService $sincronizacionHoraService)
    {
        return response()->json($sincronizacionHoraService->obtener());
    }

    /**
     * Actualiza los valores de las multas por carecer de SOA (Art. 184)
     * basándose en el PDF publicado por el BCU.
     */
    public function actualizarValoresSoa(DescargaValoresSoaService $descargaValoresSoaService)
    {
        $resultado = $descargaValoresSoaService->descargarYActualizar();

        $statusCode = $resultado['success'] ? 200 : 500;
        return response()->json($resultado, $statusCode);
    }
}
