<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Planilla {{ $planilla->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f0f0f0; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Jefatura de Policía de Montevideo</h2>
        <h3>Dirección de Tesorería</h3>
        <h1>Planilla de Depósito de Vehículos</h1>
        <p><strong>Número:</strong> {{ $planilla->numero }} | <strong>Fecha:</strong> {{ $planilla->fecha->format('d/m/Y') }}</p>
    </div>

    @if($planilla->isAnulada())
        <div style="background-color: #ffebee; padding: 10px; margin-bottom: 20px; border: 2px solid #f44336;">
            <strong>PLANILLA ANULADA</strong><br>
            Fecha: {{ $planilla->anulada_fecha->format('d/m/Y H:i') }}
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
            @foreach($planilla->depositos as $index => $deposito)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $deposito->recibo_fecha->format('d/m/Y') }}</td>
                    <td>{{ $deposito->recibo_serie }} {{ $deposito->recibo_numero }}</td>
                    <td>{{ $deposito->orden_cobro }}</td>
                    <td>{{ $deposito->titular }}</td>
                    <td>{{ $deposito->cedula }}</td>
                    <td>{{ $deposito->concepto }}</td>
                    <td>{{ $deposito->medioPago->nombre ?? '-' }}</td>
                    <td class="text-right text-nowrap">${{ number_format($deposito->monto, 2, ',', '.') }}</td>
                </tr>
                @php $total += $deposito->monto; @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="8" class="text-right">TOTAL PLANILLA:</td>
                <td class="text-right text-nowrap">${{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <script>
        window.onload = function() { window.print(); }
    </script>
</body>
</html>
