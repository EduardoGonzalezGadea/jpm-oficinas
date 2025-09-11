
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Arrendamientos - {{ $planilla->numero }}</title>
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
            <h1>Planilla de Arrendamientos</h1>
            <div style="text-align: right;">
                <p style="margin: 0;">Número: {{ $planilla->numero }}</p>
                <p style="margin: 0;">Fecha de Creación: {{ $planilla->fecha_creacion->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Ingreso</th>
                <th>Nombre</th>
                <th class="text-right">Monto</th>
                <th>Medio de Pago</th>
                <th>O/C</th>
                <th>Recibo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($planilla->arrendamientos as $arrendamiento)
                <tr>
                    <td>{{ $arrendamiento->fecha->format('d/m/Y') }}</td>
                    <td>{{ is_numeric($arrendamiento->ingreso) ? number_format($arrendamiento->ingreso, 0, ',', '.') : $arrendamiento->ingreso }}</td>
                    <td>{{ $arrendamiento->nombre }}</td>
                    <td class="text-right">{{ $arrendamiento->monto_formateado }}</td>
                    <td>{{ $arrendamiento->medio_de_pago }}</td>
                    <td>{{ is_numeric($arrendamiento->orden_cobro) ? number_format($arrendamiento->orden_cobro, 0, ',', '.') : $arrendamiento->orden_cobro }}</td>
                    <td>{{ is_numeric($arrendamiento->recibo) ? number_format($arrendamiento->recibo, 0, ',', '.') : $arrendamiento->recibo }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total Planilla:</strong></td>
                <td class="text-right"><strong>{{ '$ ' . number_format($planilla->arrendamientos->sum('monto'), 2, ',', '.') }}</strong></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>