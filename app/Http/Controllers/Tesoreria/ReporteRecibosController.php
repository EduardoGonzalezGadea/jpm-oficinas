<?php

namespace App\Http\Controllers\Tesoreria;

use App\Http\Controllers\Controller;
use App\Services\Tesoreria\ReporteRecibosService;
use App\Exports\ReporteRecibosExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        return $this->crearDescarga($filePath, $filename);
    }

    private function crearDescarga(string $filePath, string $filename): BinaryFileResponse
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ])->deleteFileAfterSend(true);
    }
}
