<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Prendas - {{ $planilla->numero }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            text-align: left;
            margin-bottom: 20px;
        }
        .header h1, .header h4, .header h5 {
            margin: 0;
        }
        .header h1 {
            font-size: 24px;
        }
        .header h4 {
            font-size: 18px;
        }
        .header h5 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
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
        .text-nowrap {
            white-space: nowrap;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="text-center no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

    <div class="header">
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <h1 style="margin: 0;">Planilla de Prendas</h1>
            <div style="text-align: right;">
                <p style="margin: 0; font-size: 12px;"><strong>Número:</strong> {{ $planilla->numero }}</p>
                <p style="margin: 0; font-size: 12px;"><strong>Fecha:</strong> {{ $planilla->fecha->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    @if($planilla->isAnulada())
        <div style="border: 2px solid red; color: red; padding: 10px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            PLANILLA ANULADA - {{ $planilla->anulada_fecha->format('d/m/Y H:i') }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Serie/N° Recibo</th>
                <th>Orden Cobro</th>
                <th>Titular</th>
                <th>Cédula</th>
                <th>Concepto</th>
                <th>Medio Pago</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($planilla->prendas as $index => $prenda)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $prenda->recibo_fecha->format('d/m/Y') }}</td>
                    <td>{{ $prenda->recibo_serie }} {{ $prenda->recibo_numero }}</td>
                    <td>{{ $prenda->orden_cobro }}</td>
                    <td>{{ $prenda->titular_nombre }}</td>
                    <td>{{ $prenda->titular_cedula }}</td>
                    <td>{{ $prenda->concepto }}</td>
                    <td>{{ $prenda->medioPago->nombre ?? '-' }}</td>
                    <td class="text-right text-nowrap">${{ number_format($prenda->monto, 2, ',', '.') }}</td>
                </tr>
                @php $total += $prenda->monto; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right"><strong>Total Planilla:</strong></td>
                <td class="text-right text-nowrap"><strong>${{ number_format($total, 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>


</body>
</html>
