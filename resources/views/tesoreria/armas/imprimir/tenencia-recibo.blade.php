{{-- resources/views/tesoreria/armas/imprimir/tenencia-recibo.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo T.H.A.T.A. {{ $tenencia->recibo }}</title>
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

        /* Posiciones exactas proporcionadas por el usuario (Ajuste -1mm vertical adicional) */
        
        /* Orden de Cobro: Altura 26mm (27-1) */
        .oc-titulo {
            top: 29mm;
            left: 152mm;
        }
        .oc-dato {
            top: 29mm;
            left: 163mm;
        }

        /* Ingreso Contabilidad: Altura 30.5mm (31.5-1) */
        .ing-titulo {
            top: 33.5mm;
            left: 152mm;
        }
        .ing-dato {
            top: 33.5mm;
            left: 163mm;
        }

        /* Trámite: Altura 35.5mm (36.5-1) */
        .tram-titulo {
            top: 38.5mm;
            left: 152mm;
        }
        .tram-dato {
            top: 38.5mm;
            left: 163mm;
        }

        /* Monto en números: Altura 57mm (58-1), Ancho 162mm (Centrado en este punto) */
        .monto-numeros {
            top: 60mm;
            left: 162mm;
            transform: translateX(-50%); /* Centrar respecto al punto left */
        }

        /* Titular: Altura 69mm (70-1), Left 68mm */
        .titular-dato {
            top: 69mm;
            left: 65mm;
        }

        /* Monto en letras: Altura 76mm (77-1), Left 68mm */
        .monto-letras {
            top: 76mm;
            left: 68mm;
        }

        /* Fecha: Altura 91mm (92-1) */
        
        /* Título MONTEVIDEO */
        .fecha-titulo {
            top: 91mm;
            left: 45mm; /* Posición estimada para el título MONTEVIDEO */
        }

        /* Día: Left 91mm */
        .fecha-dia {
            top: 91mm;
            left: 91mm;
        }

        /* Mes: Left 122mm */
        .fecha-mes {
            top: 91mm;
            left: 122mm;
            text-transform: uppercase;
        }

        /* Año: Left 168mm (169-1) */
        .fecha-anio {
            top: 91mm;
            left: 168mm;
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
                {{ $tenencia->orden_cobro ?? '' }}
            </div>

            <!-- ING. (Ingreso Contabilidad) -->
            <div class="campo ing-titulo">ING.</div>
            <div class="campo ing-dato">
                {{ $tenencia->ingreso_contabilidad ?? '' }}
            </div>

            <!-- TRAM. (Número de Trámite) -->
            <div class="campo tram-titulo">TRAM.</div>
            <div class="campo tram-dato">
                @php
                    $tramiteFormatted = $tenencia->numero_tramite ?? '';
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
                $ {{ number_format($tenencia->monto, 2, ',', '.') }}
            </div>

            <!-- Titular completo: Nombre, CI, Tel -->
            <div class="campo titular-dato">
                {{ $tenencia->titular }}, CI {{ $tenencia->cedula }}{{ $tenencia->telefono ? ', TEL. ' . $tenencia->telefono : '' }}
            </div>

            <!-- Monto en letras -->
            <div class="campo monto-letras">
                {{ $montoEnLetras }}
            </div>

            <!-- Fecha Desglosada -->
            <div class="campo fecha-titulo">MONTEVIDEO</div>
            
            <div class="campo fecha-dia">
                {{ $tenencia->fecha->format('d') }}
            </div>

            <div class="campo fecha-mes">
                {{ $tenencia->fecha->locale('es')->isoFormat('MMMM') }}
            </div>

            <div class="campo fecha-anio">
                {{ $tenencia->fecha->format('Y') }}
            </div>

        </div>
    @endfor

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

</body>

</html>
