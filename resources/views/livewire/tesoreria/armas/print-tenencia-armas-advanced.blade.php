<div id="print-container" style="background: white; padding: 20px;">
    <x-reports.header :title="$titulo" :usuario="$usuario_impresion" :fecha="$fecha_impresion" />

    <table class="table table-bordered table-sm table-striped">
        <thead class="thead-light">
            <tr>
                <th>Fecha</th>
                <th>Titular</th>
                <th>Cédula</th>
                <th>Trámite</th>
                <th>Recibo</th>
                <th>O/C</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $row)
            <tr>
                <td class="text-nowrap">{{ $row->fecha instanceof \Carbon\Carbon ? $row->fecha->format('d/m/Y') : \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
                <td>{{ $row->titular }}</td>
                <td class="text-nowrap">{{ $row->cedula }}</td>
                <td class="text-nowrap">{{ $row->numero_tramite }}</td>
                <td class="text-nowrap">{{ $row->recibo }}</td>
                <td class="text-nowrap">{{ $row->orden_cobro }}</td>
                <td class="text-right text-nowrap">$ {{ number_format($row->monto, 2, ',', '.') }}</td>
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
                <td class="text-right font-weight-bold">$ {{ number_format($total, 2, ',', '.') }}</td>
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
                margin: [10, 10, 10, 10],
                filename: filename,
                image: {
                    type: 'jpeg',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    logging: false
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

            html2pdf().set(opt).from(element).save().then(() => {
                setTimeout(() => {
                    window.close();
                }, 1500);
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