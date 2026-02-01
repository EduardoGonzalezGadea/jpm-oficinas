<div id="print-container" style="background: white; padding: 10px;">
    <style>
        @page {
            margin: 5mm;
            size: auto;
        }

        /* Body styles inherited from layout */

        .header-print {
            text-align: left;
            margin-bottom: 10px;
        }

        .header-print h4,
        .header-print h5,
        .header-print h6 {
            margin: 2px 0;
        }

        .multa-record {
            border: 1px solid #999;
            padding: 5px;
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .multa-record p {
            margin-bottom: 1px;
            line-height: 1.2;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 4px;
            margin-right: -5px;
            margin-left: -5px;
        }

        .col-7 {
            flex: 0 0 55%;
            max-width: 55%;
            padding-right: 5px;
            padding-left: 5px;
        }

        .col-5 {
            flex: 0 0 45%;
            max-width: 45%;
            padding-left: 5px;
            padding-right: 5px;
        }

        .adenda-box {
            border: 1px solid #ddd;
            padding: 4px;
            background-color: #f9f9f9;
            border-radius: 3px;
            min-height: auto;
            /* Removed fixed height for compactness */
        }

        .adenda-content {
            font-size: 0.8em;
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            line-height: 1.1;
        }

        .mb-1 {
            margin-bottom: 2px;
        }

        .mb-2 {
            margin-bottom: 4px;
        }

        .mt-2 {
            margin-top: 4px;
        }

        .items-table {
            width: 100%;
            margin-top: 4px;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ccc;
            padding: 2px 4px;
            font-size: 0.85em;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        /* Helper for non-breaking text */
        .text-nowrap {
            white-space: nowrap;
        }

        .dato-destacado {
            font-size: 1.1em;
            font-weight: bold;
            background-color: #eaeaea !important;
            border: 1px solid #888;
            padding: 2px 6px;
            border-radius: 3px;
            margin: 3px 0;
            display: block;
            width: fit-content;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .summary-print-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .summary-print-table {
            width: 70%;
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
        <h6 class="d-flex justify-content-between" style="border-bottom: 1px solid #000; padding-bottom: 5px;">
            <span>Listado Detallado de Multas Cobradas</span>
            <span>{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</span>
        </h6>
    </div>

    @forelse ($multas as $multa)
    <div class="multa-record">
        <div class="row">
            <!-- Columna izquierda: Datos principales -->
            <div class="col-7">
                <p><strong>Fecha:</strong> {{ $multa->fecha->format('d/m/Y') }}</p>
                <div class="dato-destacado">
                    <strong>Recibo:</strong> {{ $multa->recibo }}
                </div>
                <p><strong>Nombre:</strong> {{ $multa->nombre ?: 'Sin dato' }}</p>
                <p><strong>Cédula / RUT:</strong> {{ $multa->cedula ?: 'Sin dato' }}</p>
                <div class="dato-destacado">
                    <strong>Monto Total:</strong> <span class="text-nowrap">{{ $multa->monto_formateado }}</span>
                </div>
            </div>

            <!-- Columna derecha: Adenda e Información adicional -->
            <div class="col-5">
                @if($multa->adenda)
                <div class="adenda-box mb-2">
                    <p class="mb-1"><strong>Adenda:</strong></p>
                    <p class="adenda-content" style="font-family: inherit;">{{ $multa->adenda }}</p>
                </div>
                @endif

                @if($multa->adicional)
                <div class="adenda-box mb-2">
                    <p class="mb-1"><strong>Info Adicional:</strong></p>
                    <p class="adenda-content" style="font-family: inherit;">{{ $multa->adicional }}</p>
                </div>
                @endif

                @if($multa->referencias)
                <div class="adenda-box">
                    <p class="mb-1"><strong>Referencias:</strong></p>
                    <p class="adenda-content" style="font-family: inherit;">{{ $multa->referencias }}</p>
                </div>
                @endif
            </div>
        </div>

        @if($multa->items->count() > 0)
        <p class="mt-2" style="font-size: 0.9em; margin-bottom: 2px;"><strong>Detalle de Ítems:</strong></p>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Descripción</th>
                    <th style="text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($multa->items as $item)
                <tr>
                    <td>{{ $item->detalle }}</td>
                    <td>{{ $item->descripcion ?: '-' }}</td>
                    <td style="text-align: right;" class="text-nowrap">$ {{ number_format($item->importe, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @empty
    <p>No hay registros para el período seleccionado.</p>
    @endforelse

    <div style="text-align: right; margin-top: 20px; border-bottom: 2px solid #000; padding-bottom: 5px;">
        <h4>Total General de Recaudación: <span class="text-nowrap">$ {{ number_format($total, 2, ',', '.') }}</span></h4>
    </div>


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
            const filename = 'Informe Multas Cobradas Detallado del {{ \Carbon\Carbon::parse($fechaDesde)->format("d-m-Y") }} al {{ \Carbon\Carbon::parse($fechaHasta)->format("d-m-Y") }}.pdf';

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