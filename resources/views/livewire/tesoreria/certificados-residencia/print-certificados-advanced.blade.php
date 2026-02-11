<div>
    <x-reports.header :title="$titulo" :usuario="$usuario_impresion" :fecha="$fecha_impresion" />

    <table class="table table-bordered table-sm table-striped">
        <thead class="thead-light">
            <tr>
                <th>Fecha Recibido</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Documento</th>
                <th>Recibo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $row)
            <tr>
                <td class="text-nowrap">{{ $row->fecha_recibido instanceof \Carbon\Carbon ? $row->fecha_recibido->format('d/m/Y') : \Carbon\Carbon::parse($row->fecha_recibido)->format('d/m/Y') }}</td>
                <td>{{ $row->titular_nombre }}</td>
                <td>{{ $row->titular_apellido }}</td>
                <td class="text-nowrap">{{ $row->titular_nro_documento }}</td>
                <td class="text-nowrap">{{ $row->numero_recibo }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No se encontraron registros con los filtros seleccionados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.print();
        });
    </script>
</div>