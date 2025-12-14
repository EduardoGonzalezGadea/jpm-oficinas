<div>
    <div class="card">
        <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-file-alt mr-2"></i>Planilla {{ $planilla->numero }}</strong></h4>
            <div>
                <a href="{{ route('tesoreria.deposito-vehiculos.planillas.print', $planilla->id) }}" target="_blank" class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-print"></i> Imprimir
                </a>
                <a href="{{ route('tesoreria.deposito-vehiculos.planillas.index') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        <div class="card-body">
            @if($planilla->isAnulada())
                <div class="alert alert-danger mb-4">
                    <strong><i class="fas fa-ban"></i> PLANILLA ANULADA</strong><br>
                    Fecha de anulación: {{ $planilla->anulada_fecha->format('d/m/Y H:i') }}<br>
                    Anulada por: {{ $planilla->anuladaPor->nombre ?? 'N/D' }} {{ $planilla->anuladaPor->apellido ?? '' }}
                </div>
            @endif

            <div class="mb-4">
                <h4 class="mb-0">Jefatura de Policía de Montevideo</h4>
                <h5 class="mb-3 text-muted">Dirección de Tesorería</h5>
                <div class="d-flex justify-content-between align-items-end border-bottom pb-2">
                    <h2 class="mb-0">Planilla de Depósito de Vehículos</h2>
                    <div class="text-right">
                        <p class="mb-0"><strong>Número:</strong> {{ $planilla->numero }}</p>
                        <p class="mb-0"><strong>Fecha:</strong> {{ $planilla->fecha->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Serie/N° Recibo</th>
                            <th>Orden Cobro</th>
                            <th>Titular</th>
                            <th>Cédula</th>
                            <th>Concepto</th>
                            <th>Medio Pago</th>
                            <th class="text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = 0; @endphp
                        @foreach($planilla->depositos as $index => $deposito)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $deposito->recibo_fecha->format('d/m/Y') }}</td>
                                <td>{{ $deposito->recibo_serie }} {{ $deposito->recibo_numero }}</td>
                                <td>{{ $deposito->orden_cobro }}</td>
                                <td>{{ $deposito->titular }}</td>
                                <td>{{ $deposito->cedula }}</td>
                                <td>{{ $deposito->concepto }}</td>
                                <td>{{ $deposito->medioPago->nombre ?? '-' }}</td>
                                <td class="text-right text-nowrap">${{ number_format($deposito->monto, 2, ',', '.') }}</td>
                            </tr>
                            @php $total += $deposito->monto; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="8" class="text-right font-weight-bold">Total Planilla:</td>
                            <td class="text-right font-weight-bold text-nowrap">${{ number_format($total, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
