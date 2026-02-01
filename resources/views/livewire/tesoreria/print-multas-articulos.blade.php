<div id="print-container" style="padding: 20px; background: white;">
    <style>
        @page {
            margin: 10mm;
            size: auto;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header-print {
            text-align: left;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-print h4 {
            margin: 0;
            text-transform: uppercase;
        }

        .header-print h5 {
            margin: 5px 0;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 4px;
            word-wrap: break-word;
        }

        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-nowrap {
            white-space: nowrap;
        }

        .articulo-col {
            width: 8%;
        }

        .apartado-col {
            width: 10%;
        }

        .descripcion-col {
            width: 50%;
        }

        .importe-col {
            width: 16%;
        }

        .decreto-col {
            width: 16%;
        }
    </style>

    <div class="header-print">
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <div class="header-info">
            <span>Listado de Artículos de Multas de Tránsito</span>
            <span>
                UR: {{ $valorUr }} @if($mesUr) ({{ $mesUr }}) @endif -
                Fecha: {{ date('d/m/Y') }}
            </span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="articulo-col">Art.</th>
                <th class="apartado-col">Apartado</th>
                <th class="descripcion-col">Descripción</th>
                <th class="importe-col">Original</th>
                <th class="importe-col">Unificado *</th>
            </tr>
        </thead>
        <tbody>
            @foreach($multas as $multa)
            <tr>
                <td class="text-center"><strong>{{ $multa->articulo }}</strong></td>
                <td class="text-center">{{ $multa->apartado ?: '-' }}</td>
                <td>
                    {{ $multa->descripcion }}
                    @if($multa->decreto)
                    <div style="font-size: 0.85em; color: #666; font-style: italic; margin-top: 2px;">
                        {{ $multa->decreto }}
                    </div>
                    @endif
                </td>
                <td class="text-right text-nowrap">{!! $multa->importe_original_formateado !!}</td>
                <td class="text-right text-nowrap">{!! $multa->importe_unificado_formateado !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; font-size: 0.9em; font-style: italic;">
        * Unificado = Aplicable a partir de Octubre/2024.
    </div>

    <!-- Script para generación de PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const element = document.getElementById('print-container');
            const filename = 'articulos_multas_{{ now()->format("Y-m-d_H-i-s") }}.pdf';

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
                // Cerrar la ventana después de un breve delay para asegurar que la descarga inició
                setTimeout(() => {
                    window.close();
                }, 1000);
            }).catch(err => {
                console.error('Error al generar PDF:', err);
                // Si falla, al menos dejamos que el usuario vea el contenido
            });
        });
    </script>
</div>