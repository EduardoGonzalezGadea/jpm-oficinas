<div class="container-fluid">
    @section('title', 'Imprimir Reporte de Recibos')

    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .card-header { background-color: #dee2e6 !important; -webkit-print-color-adjust: exact; }
            body { font-size: 11px; }
            table { font-size: 10px; }
            .table td, .table th { padding: 2px 4px !important; }
            .thead-dark th { background-color: #343a40 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
            .bg-dark { background-color: #343a40 !important; -webkit-print-color-adjust: exact; }
            .bg-danger { background-color: #e74a3b !important; color: #fff !important; -webkit-print-color-adjust: exact; }
        }
        @media screen {
            .print-header { display: none; }
        }
    </style>

    {{-- Botón imprimir (solo pantalla) --}}
    <div class="mb-2 no-print">
        <button onclick="window.print()" class="btn btn-info btn-sm">
            <i class="fas fa-print mr-1"></i> Imprimir
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm ml-1">
            <i class="fas fa-times mr-1"></i> Cerrar
        </button>
    </div>

    @if($reporte)
    {{-- Encabezado institucional (solo impresión) --}}
    <div class="text-center mb-3 print-header" style="display: none;">
        <h5 class="font-weight-bold mb-0">INTENDENCIA DEPARTAMENTAL</h5>
        <h6 class="font-weight-bold mb-0">DIRECCIÓN DE TESORERÍA</h6>
        <p class="small mb-0">Reporte de Recibos para Contabilidad</p>
    </div>

    <div class="text-center mb-2">
        <h5 class="font-weight-bold"><i class="fas fa-clipboard-list mr-2 no-print"></i>Reporte de Recibos — Tesorería</h5>
        <p class="mb-0">Período: <strong>{{ $reporte['fecha_desde'] }}</strong> al <strong>{{ $reporte['fecha_hasta'] }}</strong></p>
    </div>

    {{-- Tabla Resumen --}}
    <table class="table table-sm table-bordered mb-3">
        <thead class="thead-dark">
            <tr>
                <th>Concepto</th>
                <th class="text-center" style="width: 120px;">Cant. Recibos</th>
                <th class="text-right" style="width: 150px;">Monto Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte['secciones'] as $seccion)
            @if($seccion['cantidad'] > 0)
            <tr>
                <td class="font-weight-bold">{{ $seccion['nombre'] }}</td>
                <td class="text-center">{{ $seccion['cantidad'] }}</td>
                <td class="text-right font-weight-bold">{{ $seccion['monto_total_formateado'] }}</td>
            </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-dark text-white font-weight-bold">
                <td>TOTAL GENERAL</td>
                <td class="text-center">{{ $reporte['gran_total_cantidad'] }}</td>
                <td class="text-right">{{ $reporte['gran_total_monto_formateado'] }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Detalle por Sección --}}
    @foreach($reporte['secciones'] as $seccion)
        @if($seccion['cantidad'] > 0)
        <h6 class="font-weight-bold mt-3 mb-1 border-bottom pb-1">
            {{ $seccion['nombre'] }}
            <small class="text-muted">({{ $seccion['cantidad'] }} recibos — {{ $seccion['monto_total_formateado'] }})</small>
        </h6>
        <table class="table table-sm table-bordered table-striped mb-2">
            <thead class="thead-light">
                <tr>
                    <th style="width: 110px;">Nro. Recibo</th>
                    <th style="width: 90px;">Fecha</th>
                    <th style="width: 120px;">Cédula</th>
                    <th>Titular</th>
                    <th class="text-right" style="width: 120px;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($seccion['registros'] as $registro)
                <tr>
                    <td>{{ $registro['recibo'] }}</td>
                    <td>{{ $registro['fecha'] }}</td>
                    <td>{{ $registro['cedula'] ?: '-' }}</td>
                    <td>{{ $registro['titular'] }}</td>
                    <td class="text-right">{{ $registro['monto_formateado'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-weight-bold bg-light">
                    <td colspan="4" class="text-right">Subtotal:</td>
                    <td class="text-right">{{ $seccion['monto_total_formateado'] }}</td>
                </tr>
            </tfoot>
        </table>
        @endif
    @endforeach

    {{-- Gran Total Final --}}
    <div class="bg-danger text-white p-2 font-weight-bold d-flex justify-content-between mt-3">
        <span><i class="fas fa-calculator mr-2 no-print"></i>TOTAL GENERAL</span>
        <span>{{ $reporte['gran_total_cantidad'] }} recibos — {{ $reporte['gran_total_monto_formateado'] }}</span>
    </div>

    <div class="mt-3 text-muted small text-center">
        Generado el {{ now()->format('d/m/Y H:i:s') }} — Dirección de Tesorería
    </div>

    @else
    <div class="alert alert-warning">No se encontraron datos para el período seleccionado.</div>
    @endif
</div>
