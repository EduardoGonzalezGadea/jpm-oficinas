<div>
    <style>
        .text-nowrap-custom {
            white-space: nowrap;
        }

        .header-print {
            text-align: left;
            margin-bottom: 10px;
        }

        .header-print h4 {
            margin: 0 0 2px 0;
            font-size: 1.2rem;
        }

        .header-print h5 {
            margin: 0 0 2px 0;
            font-size: 1rem;
        }

        .header-print h6 {
            margin: 0;
            font-size: 0.9rem;
        }

        .table {
            margin-bottom: 5px;
        }

        .table th,
        .table td {
            padding: 1px 4px;
            font-size: 0.8rem;
            line-height: 1.1;
        }

        .table th {
            font-size: 0.75rem;
            line-height: 1.1;
        }

        .table tbody tr {
            height: 18px;
        }

        .table {
            border-spacing: 0;
            border-collapse: collapse;
        }

        .totales-institucion {
            margin-bottom: 8px;
            padding: 4px 8px;
        }

        .totales-institucion h5 {
            margin: 0 0 4px 0;
            font-size: 0.9rem;
        }

        .totales-institucion .p-2 {
            padding: 2px 4px !important;
            font-size: 0.75rem;
        }
    </style>

    <div class="header-print">
        <h4>Jefatura de Policía de Montevideo</h4>
        <h5>Dirección de Tesorería</h5>
        <h6 class="d-flex justify-content-between align-items-center"><span>Listado de Eventuales</span><span>{{ ucfirst(\Carbon\Carbon::create()->month($mes)->monthName) }} {{ $year }}</span></h6>
    </div>

    @if ($totalesPorInstitucion->isNotEmpty())
    <div class="totales-institucion border rounded bg-light">
        <h5 class="text-center">Totales por Institución</h5>
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

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th class="text-center align-middle">Fecha</th>
                    <th class="text-center align-middle">Ingreso</th>
                    <th class="text-center align-middle">Institución</th>
                    <th class="text-center align-middle">Monto</th>
                    <th class="text-center align-middle">O/C</th>
                    <th class="text-center align-middle">Recibo</th>
                    <th class="text-center align-middle">Medio de Pago</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($eventuales as $eventual)
                <tr>
                    <td class="text-center align-middle">{{ $eventual->fecha->format('d/m/Y') }}</td>
                    <td class="text-right align-middle">{{ is_numeric($eventual->ingreso) ? number_format($eventual->ingreso, 0, ',', '.') : $eventual->ingreso }}</td>
                    <td class="text-center align-middle">{{ $eventual->institucion ?: 'SIN DATO' }}</td>
                    <td class="text-right align-middle"><span class="text-nowrap-custom">{{ $eventual->monto_formateado }}</span></td>
                    <td class="text-right align-middle">{{ is_numeric($eventual->orden_cobro) ? number_format($eventual->orden_cobro, 0, ',', '.') : $eventual->orden_cobro }}</td>
                    <td class="text-right align-middle">{{ is_numeric($eventual->recibo) ? number_format($eventual->recibo, 0, ',', '.') : $eventual->recibo }}</td>
                    <td class="text-center align-middle">{{ $eventual->medio_de_pago }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No hay registros para el mes y año seleccionados.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                @foreach ($subtotales as $subtotal)
                <tr>
                    <td colspan="3" class="text-right align-middle"><strong>Total {{ $subtotal->medio_de_pago }}:</strong></td>
                    <td class="text-right align-middle"><strong><span class="text-nowrap-custom">$ {{ number_format($subtotal->total, 2, ',', '.') }}</span></strong></td>
                    <td colspan="3"></td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="text-right align-middle"><strong>Total General:</strong></td>
                    <td class="text-right align-middle"><strong><span class="text-nowrap-custom">$ {{ number_format($total, 2, ',', '.') }}</span></strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.print();
        });
    </script>
</div>