<div class="container-fluid px-0">
    @section('title', 'Planillas No Confirmadas para E.R.')

    <div class="card">
        <div class="card-header bg-warning text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-clock mr-2"></i>Planillas No Confirmadas para E.R.</strong>
                </h4>
                <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left mr-1"></i>Volver a Estados de Recaudación
                </a>
            </div>
        </div>

        <div class="card-body px-2 pt-3">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th>Fecha</th>
                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Dependencia</th>
                            <th>Turno</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Confirmar</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($grupos as $fechaKey => $grupo)
                            @foreach($grupo['planillas'] as $p)
                            <tr>
                                @if($loop->first)
                                <td class="align-middle font-weight-bold" rowspan="{{ count($grupo['planillas']) }}">{{ $grupo['fecha_display'] }}</td>
                                @endif
                                <td class="align-middle">{{ $p->numero }}</td>
                                <td class="align-middle">{!! App\Helpers\FormatHelper::renderTipo($p->tipo->tipo ?? '—', $p->tipo_id) !!}</td>
                                <td class="align-middle">{{ $p->dependencia->dependencia ?? '—' }}</td>
                                <td class="align-middle">{{ $p->turno ?? '—' }}</td>
                                <td class="align-middle text-right text-nowrap">$ {{ number_format($p->items->sum('importe'), 2, ',', '.') }}</td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('tesoreria.gestion-cfe.estados-recaudacion.confirmar', $p->id) }}" class="btn btn-success btn-sm">
                                        <i class="fas fa-check mr-1"></i> Confirmar
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            @if($grupo['mostrar_total'])
                            <tr class="bg-light font-weight-bold">
                                <td colspan="5" class="text-right align-middle">Total del {{ $grupo['fecha_display'] }}</td>
                                <td class="align-middle text-right text-nowrap">$ {{ number_format($grupo['total_dia'], 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    <i class="fas fa-check-circle mr-1 text-success"></i> Todas las planillas están confirmadas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">
                    Mostrando {{ $planillas->firstItem() ?? 0 }} a {{ $planillas->lastItem() ?? 0 }}
                    de {{ $planillas->total() }} resultados
                </small>
                {{ $planillas->links() }}
            </div>
        </div>
    </div>
</div>
