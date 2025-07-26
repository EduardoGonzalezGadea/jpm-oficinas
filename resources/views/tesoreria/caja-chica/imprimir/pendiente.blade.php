{{-- resources/views/tesoreria/caja-chica/imprimir/pendiente.blade.php --}}
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
            margin: 20px;
            line-height: 1.4;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-uppercase { text-transform: uppercase; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-3 { margin-top: 15px; }
        .conBorde { border: thin solid #000000; padding: 5px; }
        .noImprimir { display: none; }
        .table { width: 100%; border-collapse: collapse; }
        .table td { padding: 8px; }
        .header { margin-bottom: 30px; }
        .header h1, .header h2, .header h3, .header h4, .header h5 { margin: 0; font-weight: bold; }
        .firmas { margin-top: 50px; display: flex; justify-content: space-around; }
        .firma { text-align: center; width: 40%; }
        .firma-linea { border-top: 1px solid #000000; padding-top: 5px; margin-top: 40px; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; font-size: 12px; }
        }
        @page {
            size: A4;
            margin: 10mm;
        }
    </style>
</head>
<body>

    <div id="noImprimirPendiente">
        <div class="no-print text-center mb-2">
            <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
            <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
        </div>
    </div>

    <div id="siImprimirPendiente">
        <!-- Pendiente De Descargo -->
        <table id="pendienteDescargo" class="table">
            <thead align="center">
                <tr>
                    <th colspan="12" class="text-left">
                        <h5><strong>JEFATURA DE POLIC&Iacute;A DE MONTEVIDEO<br/>DIRECCI&Oacute;N DE TESORER&Iacute;A<br/></strong></h5>
                    </th>
                </tr>
                <tr>
                    <th colspan="5"></th>
                    <th id="impPendienteNro" colspan="7" class="text-right">
                        PENDIENTE DE DESCARGO N&deg; {{ $pendiente->pendiente }}/{{ $pendiente->cajaChica->anio ?? '' }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="10" class="text-center">
                        Recib&iacute; de la Direcci&oacute;n de Tesorer&iacute;a de la Jefatura de Polic&iacute;a de Montevideo, la suma de
                    </td>
                    <td id="impPendienteMonto" colspan="2" class="text-center conBorde">
                        <strong>$ {{ number_format($pendiente->montoPendientes, 2, ',', '.') }}</strong>
                    </td>
                </tr>
                <tr>
                    <td id="impPendienteLetras" colspan="12" class="text-center conBorde">
                        ({{ $montoEnLetras }})
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center" style="font-weight: normal !important">
                        por concepto de caja chica de:
                    </td>
                    <td id="impPendienteDependencia" colspan="7" class="text-center conBorde">
                        <strong class="text-uppercase">{{ $pendiente->dependencia->dependencia ?? '' }}</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="12" class="text-center">
                        con la autorización del Jerarca, según Resolución vigente del 01/06/2018.
                        <strong>Este documento se debe presentar en la Dirección de Contabilidad, conjuntamente con la planilla de liquidación, al momento de hacer el reintegro.</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="12" class="text-center" style="border: none !important; font-size: 0.7em !important">
                        <br/><br/>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center">Firma de quien recibe:</td>
                    <td colspan="7" class="text-center" style="border-bottom: thin solid #000000"></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center">Aclaración:</td>
                    <td colspan="7" class="text-center" style="border-bottom: thin solid #000000"></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center">Fecha:</td>
                    <td colspan="2" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="1" class="text-center">D&iacute;a</td>
                    <td colspan="2" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="1" class="text-center">Mes</td>
                    <td colspan="1" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="1" class="text-center">A&ntilde;o</td>
                </tr>
                <tr>
                    <td colspan="12" class="text-center" style="border: none !important; font-size: 0.7em !important">
                        <br/><br/>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center">Firma y sello de quien entrega:</td>
                    <td colspan="7" class="text-center" style="border-bottom: thin solid #000000"></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center">Aclaración:</td>
                    <td colspan="7" class="text-center" style="border-bottom: thin solid #000000"></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center">Fecha:</td>
                    <td colspan="2" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="1" class="text-center">D&iacute;a</td>
                    <td colspan="2" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="1" class="text-center">Mes</td>
                    <td colspan="1" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="1" class="text-center">A&ntilde;o</td>
                </tr>
                <tr>
                    <td colspan="12" class="text-center" style="border: none !important">
                        <br/>
                    </td>
                </tr>
                {{-- <tr>
                    <td colspan="12" class="text-center">
                        ORIGINAL: Direcci&oacute;n de Contabilidad | DUPLICADO: Direcci&oacute;n de Tesorer&iacute;a
                    </td>
                </tr> --}}
                <tr>
                    <td colspan="4" class="text-center">N&uacute;mero:</td>
                    <td colspan="2" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="2" class="text-center">D&iacute;a:</td>
                    <td colspan="1" class="text-center" style="border-bottom: thin solid #000000"></td>
                    <td colspan="2" class="text-center">Mes:</td>
                    <td colspan="1" class="text-center" style="border-bottom: thin solid #000000"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- No se necesita JavaScript para la conversión, ya se hizo en el controlador -->
    <!-- El botón de impresión ya está incluido -->

</body>
</html>