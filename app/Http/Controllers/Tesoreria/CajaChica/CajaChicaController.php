<?php

namespace App\Http\Controllers\Tesoreria\CajaChica;

use App\Http\Controllers\Controller;
use App\Services\Tesoreria\CajaChicaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CajaChicaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('tesoreria.acceso');
        return view('tesoreria.caja-chica.index');
    }

    public function exportarExcel(Request $request, CajaChicaService $service)
    {
        $this->authorize('tesoreria.acceso');

        $mesActual = strtolower($request->query('mes', now()->locale('es')->translatedFormat('F')));
        $anioActual = $request->query('anio', now()->year);
        $fechaHasta = $request->query('fecha_hasta', now()->format('Y-m-d'));
        $fechaHastaStr = $fechaHasta ? Carbon::parse($fechaHasta)->endOfDay()->toDateTimeString() : now()->endOfDay()->toDateTimeString();

        $cajaChica = $service->obtenerCajaChica($mesActual, $anioActual);

        $pendientesSinFiltro = $cajaChica
            ? $service->obtenerPendientes($cajaChica, $mesActual, $anioActual, $fechaHastaStr, '')
            : collect();
        $pagosSinFiltro = $cajaChica
            ? $service->obtenerPagos($cajaChica, $mesActual, $anioActual, $fechaHastaStr, '')
            : collect();
        $totales = $cajaChica
            ? $service->calcularTotales($cajaChica, collect($pendientesSinFiltro), collect($pagosSinFiltro))
            : [];

        $fileName = 'TOTALES_CAJA_CHICA_' . strtoupper($mesActual) . '_' . $anioActual . '.xls';

        $xml = '<?xml version="1.0"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        $xml .= ' <Styles>' . "\n";
        $xml .= '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Borders/><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/><Interior/><NumberFormat/><Protection/></Style>' . "\n";
        $xml .= '  <Style ss:ID="s1"><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/><Interior ss:Color="#17a2b8" ss:Pattern="Solid"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="s2"><NumberFormat ss:Format="#,##0.00"/></Style>' . "\n";
        $xml .= '  <Style ss:ID="s3"><Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/><NumberFormat ss:Format="#,##0.00"/></Style>' . "\n";
        $xml .= ' </Styles>' . "\n";
        $xml .= ' <Worksheet ss:Name="Totales">' . "\n";
        $xml .= '  <Table>' . "\n";
        $xml .= '   <Column ss:Width="280"/>' . "\n";
        $xml .= '   <Column ss:Width="150"/>' . "\n";

        $xml .= '   <Row>' . "\n";
        $xml .= '    <Cell ss:StyleID="s1"><Data ss:Type="String">CONCEPTO</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="s1"><Data ss:Type="String">MONTO ($)</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";

        $datos = [
            ['Total Pendientes', $totales['Total Pendientes'] ?? 0, 's2'],
            ['Total Rendidos', $totales['Total Rendidos'] ?? 0, 's2'],
            ['Total Extras', $totales['Total Extras'] ?? 0, 's2'],
            ['Pagos Sin Egreso', $totales['Pagos Sin Egreso'] ?? 0, 's2'],
            ['Pent.+Pag. (Sin Rendir)', $totales['Pendientes y Pagos Sin Rendir'] ?? 0, 's2'],
            ['Total Pendientes + Pagos s/eg.', ($totales['Total Pendientes'] ?? 0) + ($totales['Pagos Sin Egreso'] ?? 0), 's3'],
            ['Saldo Pagos Directos', $totales['Saldo Pagos Directos'] ?? 0, 's2'],
            ['Recuperar (Rendidos + Extras + Pagos Dir.)', ($totales['Total Rendidos'] ?? 0) + ($totales['Total Extras'] ?? 0) + ($totales['Saldo Pagos Directos'] ?? 0), 's3'],
            ['Saldo Final', $totales['Saldo Total'] ?? 0, 's3'],
        ];

        foreach ($datos as $fila) {
            $xml .= '   <Row>' . "\n";
            $xml .= '    <Cell><Data ss:Type="String">' . htmlspecialchars($fila[0]) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="' . $fila[2] . '"><Data ss:Type="Number">' . number_format($fila[1], 2, '.', '') . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        }

        $xml .= '  </Table>' . "\n";
        $xml .= ' </Worksheet>' . "\n";
        $xml .= '</Workbook>';

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $fileName);
    }
}
