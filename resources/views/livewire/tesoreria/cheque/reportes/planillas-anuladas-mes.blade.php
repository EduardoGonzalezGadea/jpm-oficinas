@if($reporteMes && $reporteAnio)
    @if($planillasAnuladasMes->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-times-circle mr-2"></i>Planillas Anuladas - {{ \Carbon\Carbon::create()->month((int)$reporteMes)->locale('es')->monthName }} {{ $reporteAnio }}
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr class="text-center">
                                <th class="align-middle">N° Planilla</th>
                                <th class="align-middle">Fecha Generación</th>
                                <th class="align-middle">Fecha Anulación</th>
                                <th class="align-middle">Cant. Cheques</th>
                                <th class="align-middle">Monto Total</th>
                                <th class="align-middle">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planillasAnuladasMes as $planilla)
                                <tr class="text-center">
                                    <td class="align-middle">{{ $planilla->numero_planilla }}</td>
                                    <td class="align-middle">
                                        @if($planilla->fecha_generacion)
                                            {{ $planilla->fecha_generacion->format('d/m/Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $planilla->fecha_anulacion->format('d/m/Y H:i') }}</td>
                                    <td class="align-middle text-center">{{ $planilla->cheques->count() }}</td>
                                    <td class="align-middle">
                                        ${{ number_format($planilla->cheques->sum('monto'), 2, ',', '.') }}
                                    </td>
                                    <td class="align-middle">
                                        @if($planilla->motivo_anulacion)
                                            <small>{{ $planilla->motivo_anulacion }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="3" class="text-right align-middle">TOTALES</td>
                                <td class="text-center align-middle">{{ $planillasAnuladasMes->sum(function($p) { return $p->cheques->count(); }) }}</td>
                                <td class="text-right align-middle">
                                    ${{ number_format($planillasAnuladasMes->sum(function($p) { return $p->cheques->sum('monto'); }), 2, ',', '.') }}
                                </td>
                                <td class="align-middle"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3">
                    <strong>Total de planillas anuladas: {{ $planillasAnuladasMes->count() }}</strong>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>No se encontraron planillas anuladas para {{ \Carbon\Carbon::create()->month((int)$reporteMes)->locale('es')->monthName }} de {{ $reporteAnio }}.
        </div>
    @endif
@else
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>Seleccione un mes y año para ver las planillas anuladas.
    </div>
@endif
