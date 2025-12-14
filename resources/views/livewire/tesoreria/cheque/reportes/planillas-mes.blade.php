@if($reporteMes && $reporteAnio)
@if($planillasEmitidasMes->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-file-alt mr-2"></i>Planillas Emitidas - {{ \Carbon\Carbon::create()->month((int)$reporteMes)->locale('es')->monthName }} {{ $reporteAnio }}
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr class="text-center">
                        <th class="align-middle">N° Planilla</th>
                        <th class="align-middle">Fecha Generación</th>
                        <th class="align-middle">Estado</th>
                        <th class="align-middle">Cant. Cheques</th>
                        <th class="align-middle">Monto Total</th>
                        <th class="align-middle d-print-none">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($planillasEmitidasMes as $planilla)
                    <tr class="text-center">
                        <td class="align-middle">{{ $planilla->numero_planilla }}</td>
                        <td class="align-middle">
                            @if($planilla->fecha_generacion)
                            {{ $planilla->fecha_generacion->format('d/m/Y H:i') }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-success">Generada</span>
                        </td>
                        <td class="align-middle text-center">{{ $planilla->cheques->count() }}</td>
                        <td class="align-middle">
                            ${{ number_format($planilla->cheques->sum('monto'), 2, ',', '.') }}
                        </td>
                        <td class="align-middle d-print-none">
                            <a href="{{ route('tesoreria.cheques.planilla.ver', $planilla->id) }}" class="btn btn-sm btn-primary" title="Ver Planilla">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="3" class="text-right align-middle">TOTALES</td>
                        <td class="text-center align-middle">{{ $planillasEmitidasMes->sum(function($p) { return $p->cheques->count(); }) }}</td>
                        <td class="text-right align-middle">
                            ${{ number_format($planillasEmitidasMes->sum(function($p) { return $p->cheques->sum('monto'); }), 2, ',', '.') }}
                        </td>
                        <td class="align-middle d-print-none"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-3">
            <strong>Total de planillas emitidas: {{ $planillasEmitidasMes->count() }}</strong>
        </div>
    </div>
</div>
@else
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>No se encontraron planillas emitidas para {{ \Carbon\Carbon::create()->month((int)$reporteMes)->locale('es')->monthName }} de {{ $reporteAnio }}.
</div>
@endif
@else
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>Seleccione un mes y año para ver las planillas emitidas.
</div>
@endif