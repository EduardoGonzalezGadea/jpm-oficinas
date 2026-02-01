<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Porte de Armas - {{ $planilla->numero }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 11px;
        }

        .header {
            text-align: left;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
        }

        .header h4,
        .header h5 {
            margin: 0;
            font-weight: normal;
        }

        .planilla-info {
            float: right;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
        }

        .firma {
            display: inline-block;
            width: 200px;
            border-top: 1px solid #000;
            margin: 0 50px;
            padding-top: 5px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print text-center" style="margin-bottom: 20px;">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <div class="header">
        <div class="planilla-info">
            <p><strong>Número:</strong> {{ $planilla->numero }}</p>
            <p><strong>Fecha:</strong> {{ $planilla->fecha->format('d/m/Y') }}</p>
        </div>
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <h1>Planilla de Porte de Armas</h1>
    </div>

    @if($planilla->isAnulada())
    <div style="border: 2px solid red; color: red; padding: 10px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 16px;">
        PLANILLA ANULADA
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 70px;">Fecha</th>
                <th style="width: 60px;">Recibo</th>
                <th style="width: 80px;">Orden Cobro</th>
                <th style="width: 80px;">Trámite</th>
                <th>Titular</th>
                <th style="width: 70px;">Cédula</th>
                <th style="width: 80px;" class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($planilla->porteArmas as $index => $registro)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $registro->fecha->format('d/m/Y') }}</td>
                <td>{{ $registro->recibo }}</td>
                <td>{{ $registro->orden_cobro }}</td>
                <td>{{ $registro->numero_tramite }}</td>
                <td>{{ $registro->titular }}</td>
                <td>{{ $registro->cedula }}</td>
                <td class="text-right">${{ number_format($registro->monto, 2, ',', '.') }}</td>
            </tr>
            @php $total += $registro->monto; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="text-right font-weight-bold">Total General:</td>
                <td class="text-right font-weight-bold">${{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Funcionario:</strong> {{ $planilla->createdBy->nombre ?? '' }} {{ $planilla->createdBy->apellido ?? '' }}</p>
        <p><strong>Fecha Impresión:</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="footer">
        <div class="firma">Firma Funcionario</div>
        <div class="firma">Sello Tesorería</div>
    </div>
</body>

</html>