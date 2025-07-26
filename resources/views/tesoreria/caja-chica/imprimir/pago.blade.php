{{-- resources/views/tesoreria/caja-chica/imprimir/pago.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante Pago Directo {{ $pago->egresoPagos }}</title>
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
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 6px; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .header { margin-bottom: 20px; }
        .header h1, .header h2, .header h3 { margin: 0; }
        .datos-principales { margin-bottom: 20px; }
        .datos-principales div { margin-bottom: 5px; }
        .firmas { margin-top: 50px; display: flex; justify-content: space-around; }
        .firma { text-align: center; width: 40%; }
        .firma-linea { border-top: 1px solid #000; padding-top: 5px; margin-top: 40px; }
        .no-print { display: none; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="header text-center">
        <h2>JEFATURA DE POLICÍA DE MONTEVIDEO</h2>
        <h3>DIRECCIÓN DE TESORERÍA</h3>
        <h1 class="mt-3">PAGO DIRECTO N° {{ $pago->egresoPagos }}</h1>
    </div>

    <div class="datos-principales">
        <div><strong>FECHA EGRESO:</strong> {{ $pago->fechaEgresoPagos ? $pago->fechaEgresoPagos->format('d/m/Y') : '' }}</div>
        <div><strong>ACREEDOR:</strong> <span class="text-uppercase">{{ $pago->acreedor->acreedor ?? 'N/A' }}</span></div>
        <div><strong>CONCEPTO:</strong> {{ $pago->conceptoPagos }}</div>
        <div><strong>MONTO:</strong> ${{ number_format($pago->montoPagos, 2, ',', '.') }}</div>
        @if($pago->fechaIngresoPagos)
            <div><strong>FECHA INGRESO:</strong> {{ $pago->fechaIngresoPagos->format('d/m/Y') }}</div>
            <div><strong>INGRESO:</strong> {{ $pago->ingresoPagos }}</div>
            <div><strong>RECUPERADO:</strong> ${{ number_format($pago->recuperadoPagos, 2, ',', '.') }}</div>
        @endif
        <div><strong>SALDO:</strong> ${{ number_format($pago->saldo_pagos, 2, ',', '.') }}</div>
    </div>

    <!-- Si necesitas mostrar movimientos de recuperación, se podría agregar una tabla aquí -->
    <!-- Pero normalmente un pago directo no tiene movimientos como un pendiente -->

    <div class="firmas">
        <div class="firma">
            <div class="firma-linea">Firma Responsable</div>
        </div>
        <div class="firma">
            <div class="firma-linea">Firma Tesorería</div>
        </div>
    </div>

    <div class="no-print text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
        <button onclick="window.close()" class="btn btn-secondary">Cerrar</button>
    </div>

</body>
</html>