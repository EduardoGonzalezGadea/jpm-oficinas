<div>
    <style>
        .header-print {
            text-align: left;
            margin-bottom: 20px;
        }
        .arrendamiento-record {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .arrendamiento-record p {
            margin-bottom: 5px;
        }
    </style>

    <div class="header-print">
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <h6 class="d-flex justify-content-between"><span>Listado Detallado de Arrendamientos</span><span>{{ ucfirst(\Carbon\Carbon::create()->month($mes)->monthName) }} {{ $year }}</span></h6>
    </div>

    @forelse ($arrendamientos as $arrendamiento)
        <div class="arrendamiento-record">
            <p><strong>Fecha:</strong> {{ $arrendamiento->fecha->format('d/m/Y') }}</p>
            <p><strong>Ingreso:</strong> {{ is_numeric($arrendamiento->ingreso) ? number_format($arrendamiento->ingreso, 0, ',', '.') : ($arrendamiento->ingreso ?? 'Sin dato') }}</p>
            <p><strong>Nombre:</strong> {{ $arrendamiento->nombre ?? 'Sin dato' }}</p>
            <p><strong>Cédula:</strong> {{ $arrendamiento->cedula ?? 'Sin dato' }}</p>
            <p><strong>Teléfono:</strong> {{ $arrendamiento->telefono ?? 'Sin dato' }}</p>
            <p><strong>Medio de Pago:</strong> {{ $arrendamiento->medio_de_pago }}</p>
            <p><strong>Monto:</strong> {{ $arrendamiento->monto_formateado }}</p>
            <p><strong>Detalle:</strong> {{ $arrendamiento->detalle ?? 'Sin dato' }}</p>
            <p><strong>Orden de Cobro:</strong> {{ is_numeric($arrendamiento->orden_cobro) ? number_format($arrendamiento->orden_cobro, 0, ',', '.') : ($arrendamiento->orden_cobro ?? 'Sin dato') }}</p>
            <p><strong>Recibo:</strong> {{ is_numeric($arrendamiento->recibo) ? number_format($arrendamiento->recibo, 0, ',', '.') : ($arrendamiento->recibo ?? 'Sin dato') }}</p>
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