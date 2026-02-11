<div>
    <x-reports.header :title="$titulo" :usuario="$usuario_impresion" :fecha="$fecha_impresion" />

    <table class="table table-bordered table-sm table-striped">
        <thead class="thead-light">
            <tr>
                <th>Fecha</th>
                <th>Titular</th>
                <th>Cédula</th>
                <th>Recibo</th>
                <th>O/C</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $row)
            <tr>
                <td class="text-nowrap">{{ $row->recibo_fecha instanceof \Carbon\Carbon ? $row->recibo_fecha->format('d/m/Y') : \Carbon\Carbon::parse($row->recibo_fecha)->format('d/m/Y') }}</td>
                <td>{{ $row->titular_nombre }}</td>
                <td class="text-nowrap">{{ $row->titular_cedula }}</td>
                <td class="text-nowrap">{{ $row->recibo_numero }}</td>
                <td class="text-nowrap">{{ $row->orden_cobro }}</td>
                <td class="text-right text-nowrap">$ {{ number_format($row->monto, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No se encontraron registros con los filtros seleccionados.</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-weight-bold">Total General:</td>
                <td class="text-right font-weight-bold">$ {{ number_format($total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.print();
        });
    </script>
</div>