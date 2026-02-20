<div id="print-container" style="background: white; padding: 20px;">
    <x-reports.header :title="$titulo" :usuario="$usuario_impresion" :fecha="$fecha_impresion" />
    <style>
        @page {
            margin: 10mm;
            size: auto;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            vertical-align: middle;
            word-break: break-word;
            white-space: normal;
        }
    </style>

    <table class="table table-bordered table-sm table-striped">
        <thead class="thead-light">
            <tr>
                <th>Fecha</th>
                <th>Recibo</th>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Items (Resumen)</th>
                <th>Pago</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($multas as $multa)
            <tr>
                <td class="text-nowrap">{{ $multa->fecha->format('d/m/Y') }}</td>
                <td class="text-nowrap">{{ $multa->recibo }}</td>
                <td>{{ $multa->nombre }}</td>
                <td class="text-nowrap">{{ $multa->cedula }}</td>
                <td>
                    <ul class="list-unstyled mb-0" style="font-size: 0.85em;">
                        @foreach($multa->items as $item)
                        <li>- {{ $item->detalle }}</li>
                        @endforeach
                    </ul>
                </td>
                <td>{{ $this->formatearFormaPagoUy($multa->forma_pago) }}</td>
                <td class="text-right text-nowrap">{{ $multa->monto_formateado }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No se encontraron registros con los filtros seleccionados.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right font-weight-bold">Total General:</td>
                <td class="text-right font-weight-bold">${{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if($isPdf)
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const element = document.getElementById('print-container');
            const filename = '{{ $titulo }} - {{ date("d-m-Y") }}.pdf';
            const opt = {
                margin: [5, 5, 5, 5],
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    letterRendering: true,
                    scrollY: 0,
                    windowWidth: document.getElementById('print-container').scrollWidth
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

            const generarPdf = async () => {
                window.scrollTo(0, 0);

                if (document.fonts && document.fonts.ready) {
                    try {
                        await document.fonts.ready;
                    } catch (e) {}
                }
                // Dar tiempo extra a renderizar contenido extenso
                await new Promise(resolve => setTimeout(resolve, 2000));

                return html2pdf().set(opt).from(element).save();
            };

            generarPdf().then(() => {
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