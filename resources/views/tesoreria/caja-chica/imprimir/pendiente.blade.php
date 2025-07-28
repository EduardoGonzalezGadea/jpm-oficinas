<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Pendiente {{ $pendiente->pendiente }}/{{ $pendiente->cajaChica->anio ?? '' }}</title>
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
            <!-- Pendiente De Descargo -->
            <table class="table">
                <thead align="center">
                    <tr>
                        <th colspan="6" class="text-left">
                            <h3 style="margin: 0px;">
                                <strong>
                                    JEFATURA DE POLIC&Iacute;A DE MONTEVIDEO
                                </strong>
                            </h3>
                        </th>
                        <th colspan="4" class="text-center"></th>
                        <th colspan="2" class="text-center conBorde">{{ date('d/m/Y') }}</th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-left">
                            <h3 style="margin: 0px;">
                                <strong>
                                    DIRECCI&Oacute;N DE TESORER&Iacute;A
                                </strong>
                            </h3>
                        </th>
                    </tr>
                    <tr>
                        <th colspan="5"></th>
                        <th colspan="7" class="text-right">
                            PENDIENTE DE DESCARGO N&deg;
                            {{ $pendiente->pendiente }}/{{ $pendiente->cajaChica->anio ?? '' }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="10" class="text-center">
                            Recib&iacute; de la Direcci&oacute;n de Tesorer&iacute;a de la Jefatura de Polic&iacute;a de
                            Montevideo, la suma de
                        </td>
                        <td colspan="2" class="text-center conBorde">
                            <strong>$ {{ number_format($pendiente->montoPendientes, 2, ',', '.') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12" class="text-center conBorde">
                            {{ $montoEnLetras }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-center">
                            por concepto de caja chica de:
                        </td>
                        <td colspan="7" class="text-center conBorde">
                            <strong class="text-uppercase">{{ $pendiente->dependencia->dependencia ?? '' }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12" class="text-center">
                            con la autorización del Jerarca, según Resolución vigente del 01/06/2018.
                            <strong>Este documento se debe presentar en la Dirección de Contabilidad, conjuntamente con
                                la planilla de liquidación, al momento de hacer el reintegro.</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="12"></td>
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
            <div class="divider" style="margin-top: 90px; margin-bottom: 50px;"></div>
        @endif
    @endfor

</body>

</html>
