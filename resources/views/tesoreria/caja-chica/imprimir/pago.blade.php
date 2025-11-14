{{-- resources/views/tesoreria/caja-chica/imprimir/pago.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Pago Directo {{ $pago->egresoPagos }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 10mm;
            line-height: 1.4;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .conBorde {
            border: thin solid #000000;
            padding: 5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table td {
            padding: 8px;
        }

        .copia {
            height: 48%;
            /* Aproximadamente la mitad de la hoja A4 con márgenes */
            page-break-inside: avoid;
            margin: 2px;
        }

        .divider {
            border-top: 1px dashed #aaa;
            margin: 10px 0;
        }

        .no-print {
            display: block;
            margin-bottom: 10px;
        }

        /* Estilos para impresión */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
                font-size: 12px;
            }
        }

        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>
</head>

<body>

    <div class="no-print text-center">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

    @for ($i = 0; $i < 2; $i++)
        <div class="copia">

            <!-- Comprobante Pago Directo -->
            <table class="table">
                <thead align="center">
                    <tr>
                        <th colspan="12" class="text-left">
                            <h3>
                                <strong>
                                    JEFATURA DE POLIC&Iacute;A DE MONTEVIDEO<br />
                                    DIRECCI&Oacute;N DE TESORER&Iacute;A
                                </strong>
                            </h3>
                            @isset($reportTitle)
                                <h2 class="text-center" style="margin-top: 10px; margin-bottom: 10px;">
                                    <strong>{{ $reportTitle }}</strong>
                                </h2>
                            @endisset
                        </th>
                    </tr>
                    <tr>
                    <tr>
                        <th colspan="9" class="text-center conBorde">
                            <strong>
                                COMPROBANTE DE PAGO DIRECTO
                                {{ $pago->egresoPagos ? 'DEL EGRESO N° ' . $pago->egresoPagos . '/' . $pago->cajaChica->anio : '' }}
                            </strong>
                        </th>
                        <th colspan="3" class="text-center conBorde">
                            {{ $pago->fechaEgresoPagos ? $pago->fechaEgresoPagos->format('d/m/Y') : 'AÑO ' . $pago->cajaChica->anio ?? '' }}
                        </th>
                    </tr>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="9" class="text-center conBorde">
                            <strong class="text-uppercase">{{ $pago->acreedor->acreedor ?? 'SIN DATO' }}</strong>
                        </td>
                        <td colspan="3" class="text-center">
                            recibe de la Direcci&oacute;n de Tesorer&iacute;a
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12" class="text-center conBorde">
                            la suma de
                            <strong>$ {{ number_format($pago->montoPagos, 2, ',', '.') }}</strong>
                            ({{ $montoEnLetras }})
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12" class="text-center conBorde">
                            por concepto de {{ $pago->conceptoPagos }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="12"><br /></td>
                    </tr>

                    <tr>
                        <td colspan="5" class="text-right">Firma de quien recibe:</td>
                        <td colspan="7" class="text-center" style="border-bottom: thin solid #000"></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-right">Aclaración:</td>
                        <td colspan="7" class="text-center" style="border-bottom: thin solid #000"></td>
                    </tr>

                    <tr>
                        <td colspan="12"><br /></td>
                    </tr>

                    <tr>
                        <td colspan="5" class="text-right">Firma de quien entrega:</td>
                        <td colspan="7" class="text-center" style="border-bottom: thin solid #000"></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-right">Aclaración:</td>
                        <td colspan="7" class="text-center" style="border-bottom: thin solid #000"></td>
                    </tr>

                </tbody>
            </table>
        </div>

        @if ($i === 0)
            <div class="divider" style="margin-top: 100px; margin-bottom: 30px;"></div>
        @endif
    @endfor

    <div class="no-print text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

</body>

</html>
