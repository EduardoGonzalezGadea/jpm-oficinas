<div>
    <style>
        .header-print {
            text-align: left;
            margin-bottom: 20px;
        }

        .eventual-record {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .eventual-record p {
            margin-bottom: 5px;
        }
    </style>

    <div class="header-print">
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <h6 class="d-flex justify-content-between"><span>Listado Detallado de Eventuales</span><span>{{ ucfirst(\Carbon\Carbon::create()->month($mes)->monthName) }} {{ $year }}</span></h6>
    </div>

    @if ($totalesPorInstitucion->isNotEmpty())
    <div class="mb-3 p-3 border rounded bg-light">
        <h5 class="mb-3 text-center">Totales por Institución</h5>
        <div class="d-flex flex-wrap justify-content-around">
            @foreach ($totalesPorInstitucion as $totalInst)
            <div class="p-2 text-center flex-fill">
                <strong>{{ $totalInst->institucion ?: 'SIN DATO' }}</strong><br>
                $ {{ number_format((float) $totalInst->total_monto, 2, ',', '.') }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @forelse ($eventuales as $eventual)
    <div class="eventual-record">
        <p><strong>Fecha:</strong> {{ $eventual->fecha->format('d/m/Y') }}</p>
        <p><strong>Ingreso:</strong> {{ is_numeric($eventual->ingreso) ? number_format($eventual->ingreso, 0, ',', '.') : ($eventual->ingreso ?? 'Sin dato') }}</p>
        <p><strong>Institución:</strong> {{ $eventual->institucion ?: 'SIN DATO' }}</p>
        <p><strong>Titular:</strong> {{ $eventual->titular ?? 'Sin dato' }}</p>
        <p><strong>Medio de Pago:</strong> {{ $eventual->medio_de_pago }}</p>
        <p><strong>Monto:</strong> {{ $eventual->monto_formateado }}</p>
        <p><strong>Detalle:</strong> {{ $eventual->detalle ?? 'Sin dato' }}</p>
        <p><strong>Orden de Cobro:</strong> {{ is_numeric($eventual->orden_cobro) ? number_format($eventual->orden_cobro, 0, ',', '.') : ($eventual->orden_cobro ?? 'Sin dato') }}</p>
        <p><strong>Recibo:</strong> {{ is_numeric($eventual->recibo) ? number_format($eventual->recibo, 0, ',', '.') : ($eventual->recibo ?? 'Sin dato') }}</p>
    </div>
    @empty
    <p>No hay registros para el mes y año seleccionados.</p>
    @endforelse

    <div style="text-align: right; margin-top: 20px;">
        <h4>Total General: ${{ number_format($total, 2, ',', '.') }}</h4>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.print();
        });
    </script>
</div>