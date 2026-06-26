<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReporteRecibosExport
{
    protected Spreadsheet $spreadsheet;
    protected $sheet;
    protected int $row = 1;

    /**
     * Genera el archivo Excel y retorna la ruta temporal.
     */
    public function generar(array $reporte): string
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Reporte de Recibos');

        // Anchos de columna
        $this->sheet->getColumnDimension('A')->setWidth(28);
        $this->sheet->getColumnDimension('B')->setWidth(14);
        $this->sheet->getColumnDimension('C')->setWidth(18);
        $this->sheet->getColumnDimension('D')->setWidth(40);
        $this->sheet->getColumnDimension('E')->setWidth(18);

        // Encabezado institucional
        $this->escribirEncabezado($reporte);

        // Tabla resumen
        $this->escribirResumen($reporte);

        // Detalle por sección
        foreach ($reporte['secciones'] as $seccion) {
            if ($seccion['cantidad'] > 0) {
                $this->escribirSeccion($seccion);
            }
        }

        // Gran total
        $this->escribirGranTotal($reporte);

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'reporte_recibos_' . uniqid() . '.xlsx';
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    protected function escribirEncabezado(array $reporte): void
    {
        $this->sheet->mergeCells("A{$this->row}:E{$this->row}");
        $this->cell('A', 'DIRECCIÓN DE TESORERÍA — REPORTE DE RECIBOS');
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->row++;

        $this->sheet->mergeCells("A{$this->row}:E{$this->row}");
        $this->cell('A', "Período: {$reporte['fecha_desde']} al {$reporte['fecha_hasta']}");
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['size' => 11, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->row += 2;
    }

    protected function escribirResumen(array $reporte): void
    {
        // Encabezados de resumen
        $this->sheet->mergeCells("A{$this->row}:E{$this->row}");
        $this->cell('A', 'RESUMEN POR CONCEPTO');
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->row++;

        // Columnas resumen
        $headers = ['Concepto', '', '', 'Cant. Recibos', 'Monto Total'];
        $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
        $this->cell('A', $headers[0]);
        $this->cell('D', $headers[3]);
        $this->cell('E', $headers[4]);
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6C757D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->row++;

        foreach ($reporte['secciones'] as $seccion) {
            $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
            $this->cell('A', $seccion['nombre']);
            $this->cell('D', $seccion['cantidad']);
            $this->cell('E', $seccion['monto_total']);
            $this->style("D{$this->row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $this->style("E{$this->row}", [
                'numberFormat' => ['formatCode' => '#,##0.00'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $this->style("A{$this->row}:E{$this->row}", [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $this->row++;
        }

        // Total general del resumen
        $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
        $this->cell('A', 'TOTAL GENERAL');
        $this->cell('D', $reporte['gran_total_cantidad']);
        $this->cell('E', $reporte['gran_total_monto']);
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->style("D{$this->row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $this->style("E{$this->row}", [
            'numberFormat' => ['formatCode' => '#,##0.00'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $this->row += 2;
    }

    protected function escribirSeccion(array $seccion): void
    {
        // Título de sección
        $this->sheet->mergeCells("A{$this->row}:E{$this->row}");
        $this->cell('A', "{$seccion['nombre']} ({$seccion['cantidad']} recibos — {$seccion['monto_total_formateado']})");
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E74A3B']],
        ]);
        $this->row++;

        // Encabezados de detalle
        $headers = ['Nro. Recibo', 'Fecha', 'Cédula / RUC', 'Titular', 'Monto'];
        foreach (['A', 'B', 'C', 'D', 'E'] as $i => $col) {
            $this->cell($col, $headers[$i]);
        }
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DEE2E6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->row++;

        // Filas de datos
        foreach ($seccion['registros'] as $registro) {
            $this->cell('A', $registro['recibo']);
            $this->cell('B', $registro['fecha']);
            $this->cell('C', $registro['cedula'] ?: '-');
            $this->cell('D', $registro['titular']);
            $this->cell('E', $registro['monto']);
            $this->style("E{$this->row}", [
                'numberFormat' => ['formatCode' => '#,##0.00'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $this->style("A{$this->row}:E{$this->row}", [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $this->row++;
        }

        // Subtotal
        $this->sheet->mergeCells("A{$this->row}:D{$this->row}");
        $this->cell('A', "Subtotal {$seccion['nombre']}:");
        $this->cell('E', $seccion['monto_total']);
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->style("A{$this->row}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);
        $this->style("E{$this->row}", [
            'numberFormat' => ['formatCode' => '#,##0.00'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $this->row += 2;
    }

    protected function escribirGranTotal(array $reporte): void
    {
        $this->sheet->mergeCells("A{$this->row}:C{$this->row}");
        $this->cell('A', 'TOTAL GENERAL');
        $this->cell('D', $reporte['gran_total_cantidad'] . ' recibos');
        $this->cell('E', $reporte['gran_total_monto']);
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E74A3B']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $this->style("E{$this->row}", [
            'numberFormat' => ['formatCode' => '#,##0.00'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $this->row += 2;

        // Pie de página
        $this->sheet->mergeCells("A{$this->row}:E{$this->row}");
        $this->cell('A', 'Generado el ' . now()->format('d/m/Y H:i:s') . ' — Dirección de Tesorería');
        $this->style("A{$this->row}:E{$this->row}", [
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '6C757D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    /**
     * Helpers
     */
    protected function cell(string $col, $value): void
    {
        $this->sheet->setCellValue("{$col}{$this->row}", $value);
    }

    protected function style(string $range, array $styles): void
    {
        $this->sheet->getStyle($range)->applyFromArray($styles);
    }
}
