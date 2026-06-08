<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Services\Tesoreria\ReporteRecibosService;
use App\Exports\ReporteRecibosExport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteRecibosController extends Controller
{
    public function exportarExcel(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $service = new ReporteRecibosService();
        $reporte = $service->generarReporte(
            Carbon::parse($request->desde),
            Carbon::parse($request->hasta)
        );

        $export = new ReporteRecibosExport();
        $filePath = $export->generar($reporte);

        $filename = 'reporte_recibos_' . $request->desde . '_a_' . $request->hasta . '.xlsx';

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
