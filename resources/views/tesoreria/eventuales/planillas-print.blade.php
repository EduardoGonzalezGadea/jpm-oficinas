<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Eventuales - {{ $planilla->numero }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .planilla-info {
            margin-bottom: 20px;
        }
        .planilla-info p {
            margin: 5px 0;
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
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Planilla de Eventuales</h1>
            <div style="text-align: right;">
                <p style="margin: 0;">Número: {{ $planilla->numero }}</p>
                <p style="margin: 0;">Fecha de Creación: {{ $planilla->fecha_creacion->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    

    <table>
        <thead>
            <tr>
                <th>Ingreso</th>
                <th>Institución</th>
                <th class="text-right">Monto</th>
                <th>Medio de Pago</th>
                <th>O/C</th>
                <th>Recibo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($planilla->eventuales as $eventual)
                <tr>
                    <td>{{ is_numeric($eventual->ingreso) ? number_format($eventual->ingreso, 0, ',', '.') : $eventual->ingreso }}</td>
                    <td>{{ $eventual->institucion }}</td>
                    <td class="text-right">{{ $eventual->monto_formateado }}</td>
                    <td>{{ $eventual->medio_de_pago }}</td>
                    <td>{{ is_numeric($eventual->orden_cobro) ? number_format($eventual->orden_cobro, 0, ',', '.') : $eventual->orden_cobro }}</td>
                    <td>{{ is_numeric($eventual->recibo) ? number_format($eventual->recibo, 0, ',', '.') : $eventual->recibo }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right"><strong>Total Planilla:</strong></td>
                <td class="text-right"><strong>{{ '$ ' . number_format($planilla->eventuales->sum('monto'), 2, ',', '.') }}</strong></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
