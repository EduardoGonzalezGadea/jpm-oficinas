{{-- resources/views/tesoreria/armas/imprimir/porte-recibo.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo P.A. {{ $porte->recibo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }

        .recibo-container {
            width: 210mm;
            height: 297mm;
            /* A4 completo vertical */
            position: relative;
            page-break-after: always;
            padding: 0;
            margin: 0;
        }

        .recibo-container:last-child {
            page-break-after: auto;
        }

        .campo {
            position: absolute;
            font-size: 10pt;
            white-space: nowrap;
        }

        .campo-bold {
            font-weight: bold;
        }

        /* Posiciones exactas proporcionadas por el usuario */
        
        /* 1) Orden de Cobro: 118mm ancho, 11mm alto */
        .oc-titulo {
            top: 11mm;
            left: 118mm;
        }
        .oc-dato {
            top: 11mm;
            left: 129mm;
        }

        /* 2) Ingreso Contabilidad: 118mm ancho, 16mm alto */
        .ing-titulo {
            top: 16mm;
            left: 118mm;
        }
        .ing-dato {
            top: 16mm;
            left: 129mm;
        }

        /* 3) Porte (número de trámite): 118mm ancho, 20.5mm alto */
        .porte-titulo {
            top: 20.5mm;
            left: 118mm;
        }
        .porte-dato {
            top: 20.5mm;
            left: 125mm;
        }

        /* 4) Monto en números: 157mm ancho, 25mm alto */
        .monto-numeros {
            top: 25mm;
            left: 157mm;
            transform: translateX(-50%); /* Centrar respecto al punto left */
        }

        /* 5) Titular, CI, TEL: 58mm ancho, 35mm alto */
        .titular-dato {
            top: 35mm;
            left: 58mm;
        }

        /* 6) Monto en letras: 58mm ancho, 50mm alto */
        .monto-letras {
            top: 50mm;
            left: 58mm;
        }

        /* 7) MONTEVIDEO: 35mm ancho, 66mm alto */
        .fecha-titulo {
            top: 66mm;
            left: 35mm;
        }

        /* 8) Día: 76mm ancho, 66mm alto */
        .fecha-dia {
            top: 66mm;
            left: 76mm;
        }

        /* 9) Mes: 110mm ancho, 66mm alto */
        .fecha-mes {
            top: 66mm;
            left: 110mm;
            text-transform: uppercase;
        }

        /* 10) Año: 164mm ancho, 66mm alto */
        .fecha-anio {
            top: 66mm;
            left: 164mm;
        }

        .no-print {
            display: block;
            margin: 20px;
            text-align: center;
        }

        /* Estilos para impresión */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
            }

            .recibo-container {
                margin: 0;
                padding: 0;
            }
        }

        @page {
            size: A4 portrait;
            margin: 0;
            duplex: simplex; /* Intento de forzar impresión a una cara (soporte limitado) */
        }
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

    @for ($i = 0; $i < 3; $i++)
        <div class="recibo-container">

            <!-- O/C (Orden de Cobro) -->
            <div class="campo oc-titulo">O/C</div>
            <div class="campo oc-dato">
                {{ $porte->orden_cobro ?? '' }}
            </div>

            <!-- ING. (Ingreso Contabilidad) -->
            <div class="campo ing-titulo">ING.</div>
            <div class="campo ing-dato">
                {{ $porte->ingreso_contabilidad ?? '' }}
            </div>

            <!-- P. (Número de Trámite de Porte) -->
            <div class="campo porte-titulo">P.</div>
            <div class="campo porte-dato">
                @php
                    $tramiteFormatted = $porte->numero_tramite ?? '';
                    if ($tramiteFormatted && strpos($tramiteFormatted, '/') !== false) {
                        $parts = explode('/', $tramiteFormatted);
                        if (count($parts) == 2 && strlen($parts[1]) == 4) {
                            $tramiteFormatted = $parts[0] . '/' . substr($parts[1], -2);
                        }
                    }
                @endphp
                {{ $tramiteFormatted }}
            </div>

            <!-- Monto en números (Centrado) -->
            <div class="campo campo-bold monto-numeros">
                $ {{ number_format($porte->monto, 2, ',', '.') }}
            </div>

            <!-- Titular completo: Nombre, CI, Tel -->
            <div class="campo titular-dato">
                {{ $porte->titular }}, C.I. {{ $porte->cedula }}{{ $porte->telefono ? ', TEL. ' . $porte->telefono : '' }}
            </div>

            <!-- Monto en letras -->
            <div class="campo monto-letras">
                {{ $montoEnLetras }}
            </div>

            <!-- Fecha Desglosada -->
            <div class="campo fecha-titulo">MONTEVIDEO</div>
            
            <div class="campo fecha-dia">
                {{ $porte->fecha->format('d') }}
            </div>

            <div class="campo fecha-mes">
                {{ $porte->fecha->locale('es')->isoFormat('MMMM') }}
            </div>

            <div class="campo fecha-anio">
                {{ $porte->fecha->format('Y') }}
            </div>

        </div>
    @endfor

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

</body>

</html>
