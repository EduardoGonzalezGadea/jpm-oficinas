<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TOTALES DE CAJA CHICA AL {{ $fechaHasta }}</title>
</head>
<body>
    <h4>TOTALES DE CAJA CHICA AL {{ $fechaHasta }}</h4>
    <h5>Mes: {{ $mes }} - Año: {{ $anio }}</h5>
    <table border="1">
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $concepto => $valor)
                <tr>
                    <td>{{ $concepto }}</td>
                    <td align="right">{{ is_numeric($valor) ? number_format($valor, 2, ',', '.') : $valor }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>