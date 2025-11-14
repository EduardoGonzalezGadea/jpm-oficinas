<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Planilla de Cheques</title>
    <link href="{{ asset('libs/bootstrap-4.6.2-dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape; /* Orientación horizontal */
        }
        @media print {
            .no-print {
                display: none;
            }
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container-fluid">
        <div class="no-print my-3 text-center">
            <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
            <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
        </div>

        <div class="print-header">
            <h2>JEFATURA DE POLICÍA DE MONTEVIDEO</h2>
            <h3>DIRECCIÓN DE TESORERÍA</h3>
            @if(!empty($reportTitle))
                <h4 class="d-print-none" style="margin-top: 15px;"><strong>{{ $reportTitle }}</strong></h4>
            @endif
        </div>

        {{-- Aquí se incluye el contenido de la planilla, tal como se ve en pantalla --}}
        {{-- Se pasa el ID de la planilla al componente Livewire --}}
        @livewire('tesoreria.cheque.planilla-ver', ['id' => $planilla->id])
    </div>

    <script>
        // Escuchar el evento afterprint para cerrar la ventana
        window.addEventListener('afterprint', (event) => {
            window.close();
        });
    </script>
</body>
</html>
