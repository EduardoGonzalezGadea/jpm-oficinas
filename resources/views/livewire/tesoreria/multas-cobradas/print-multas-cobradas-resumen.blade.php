<div id="print-container" style="background: white; padding: 10px;">
    <style>
        @page {
            margin: 10mm;
            size: auto;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            padding-right: 2px;
            padding-left: 2px;
        }

        .header-print {
            text-align: left;
            margin-bottom: 20px;
        }

        .header-print h4,
        .header-print h5,
        .header-print h6 {
            margin: 2px 0;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
            margin-top: 10px;
            margin-bottom: 20px;
            table-layout: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: middle;
            word-break: break-word;
            white-space: normal;
        }

        th {
            background-color: #e9ecef;
            text-align: center;
            font-weight: bold;
            white-space: nowrap;
            /* Header text nowrap */
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-nowrap {
            white-space: nowrap;
        }

        .footer-total {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .section-title {
            font-size: 1.1em;
            font-weight: bold;
            margin-top: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            display: inline-block;
        }

        .summary-print-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .summary-print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
        }

        .summary-print-table th,
        .summary-print-table td {
            border: 1px solid #000;
            padding: 6px 10px;
        }

        .summary-print-table th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
        }

        .summary-print-table .total-row {
            background-color: #333 !important;
            color: #fff !important;
            font-weight: bold;
            -webkit-print-color-adjust: exact;
        }
    </style>

    <div class="header-print">
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 5px;">
            <span style="font-weight: bold;">Resumen de Multas Cobradas (Agrupado)</span>
            <span style="font-weight: bold;">{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- Tabla Resumen -->
    <table>
        <thead>
            <tr>
                <th>Artículo</th>
                <th>Apartado</th>
                <th>Concepto / Descripción</th>
                <th>Valor</th>
                <th>Cant.</th>
                <th>%</th>
                <th>Importe</th>
                <th>% $</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itemsGrouped as $item)
            <tr>
                <td class="text-center">{{ $item->articulo }}</td>
                <td class="text-center">{{ $item->apartado ?: '-' }}</td>
                <td>{{ $item->descripcion_display }}</td>
                <td class="text-right text-nowrap">{{ $item->valor_unitario_display }}</td>
                <td class="text-center">{{ $item->cantidad }}</td>
                <td class="text-center">{{ $totalCantidad > 0 ? number_format(($item->cantidad / $totalCantidad) * 100, 1, ',', '.') : 0 }}%</td>
                <td class="text-right text-nowrap">$ {{ number_format($item->importe_total, 2, ',', '.') }}</td>
                <td class="text-center">{{ $totalGeneral > 0 ? number_format(($item->importe_total / $totalGeneral) * 100, 1, ',', '.') : 0 }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No hay registros clasificados para el período seleccionado.</td>
            </tr>
            @endforelse

            <!-- Fila de Resumen de No Clasificados (SI existen) -->
            @if($itemsUnclassified->count() > 0)
            <tr style="background-color: #fff3cd;">
                <td class="text-center" colspan="2">OTROS</td>
                <td>Items sin clasificar (Ver detalle abajo)</td>
                <td class="text-right">Varios</td>
                <td class="text-center">{{ $itemsUnclassified->count() }}</td>
                <td class="text-center">{{ $totalCantidad > 0 ? number_format(($itemsUnclassified->count() / $totalCantidad) * 100, 1, ',', '.') : 0 }}%</td>
                <td class="text-right text-nowrap">$ {{ number_format($itemsUnclassified->sum('importe'), 2, ',', '.') }}</td>
                <td class="text-center">{{ $totalGeneral > 0 ? number_format(($itemsUnclassified->sum('importe') / $totalGeneral) * 100, 1, ',', '.') : 0 }}%</td>
            </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="footer-total">
                <td colspan="4" class="text-right">Total General:</td>
                <td class="text-center">{{ $totalCantidad }}</td>
                <td class="text-center">100%</td>
                <td class="text-right text-nowrap">$ {{ number_format($totalGeneral, 2, ',', '.') }}</td>
                <td class="text-center">100%</td>
            </tr>
        </tfoot>
    </table>

    <!-- Desglose de No Clasificados -->
    @if($itemsUnclassified->count() > 0)
    <div style="page-break-inside: avoid;">
        <div class="section-title">Detalle de Ítems No Clasificados (Otros)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Fecha</th>
                    <th style="width: 15%;">Recibo</th>
                    <th style="width: 55%;">Detalle / Descripción Original</th>
                    <th style="width: 15%;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itemsUnclassified as $unclassified)
                <tr>
                    <td class="text-center">{{ $unclassified->cobrada->fecha->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $unclassified->cobrada->recibo }}</td>
                    <td>
                        {{ $unclassified->detalle }}
                        @if($unclassified->descripcion)
                        <span style="color: #555; font-style: italic;">({{ $unclassified->descripcion }})</span>
                        @endif
                    </td>
                    <td class="text-right text-nowrap">$ {{ number_format($unclassified->importe, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif


    @php
    $subtotales = $totalesPorMedio->filter(fn($item) => $item->es_subtotal);
    $combinados = $totalesPorMedio->filter(fn($item) => $item->es_combinacion || $item->es_subtotal_combinado);
    $sumaResumen = $subtotales->sum('total');
    @endphp

    @if($subtotales->count() > 0)
    <div class="summary-print-container">
        <table class="summary-print-table">
            <thead>
                <tr>
                    <th colspan="2" style="text-align: center;">Resumen de Ingresos por Medio de Pago</th>
                </tr>
                <tr>
                    <th>Medio de Pago</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subtotales as $tpm)
                <tr>
                    <td style="text-transform: uppercase; font-weight: bold;">{{ $tpm->forma_pago ?: 'SIN DATOS' }}</td>
                    <td style="text-align: right; font-weight: bold;">$ {{ number_format($tpm->total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td style="text-align: right;">TOTAL GENERAL:</td>
                    <td style="text-align: right;">$ {{ number_format($sumaResumen, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if($isPdf)
    <!-- Script para generación de PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const element = document.getElementById('print-container');
            const filename = 'Informe Multas Cobradas Resumen del {{ \Carbon\Carbon::parse($fechaDesde)->format("d-m-Y") }} al {{ \Carbon\Carbon::parse($fechaHasta)->format("d-m-Y") }}.pdf';

            const opt = {
                margin: [10, 10, 10, 10],
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    letterRendering: true
                },
                jsPDF: {
                    unit: 'mm',
                    format: 'a4',
                    orientation: 'portrait'
                },
                pagebreak: {
                    mode: ['css', 'legacy']
                }
            };

            // Generar y descargar PDF
            html2pdf().set(opt).from(element).save().then(() => {
                setTimeout(() => {
                    window.close();
                }, 1000);
            }).catch(err => {
                console.error('Error al generar PDF:', err);
            });
        });
    </script>
    @else
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.print();
        });
    </script>
    @endif
</div>
