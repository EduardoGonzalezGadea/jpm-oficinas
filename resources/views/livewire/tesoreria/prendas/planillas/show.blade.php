<div>
    <div class="card">
        <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-file-alt mr-2"></i>Planilla {{ $planilla->numero }}</strong></h4>
            <div>
                <a href="{{ route('tesoreria.prendas.planillas.print', $planilla->id) }}" target="_blank" class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-print"></i> Imprimir
                </a>
                <a href="{{ route('tesoreria.prendas.planillas.index') }}" class="btn btn-light btn-sm">
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

            <!-- Header similar to print view -->
            <div class="mb-4">
                <h4 class="mb-0">Jefatura de Policía de Montevideo</h4>
                <h5 class="mb-3 text-muted">Dirección de Tesorería</h5>
                <div class="d-flex justify-content-between align-items-end border-bottom pb-2">
                    <h2 class="mb-0">Planilla de Prendas</h2>
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
                        @foreach($planilla->prendas as $index => $prenda)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $prenda->recibo_fecha->format('d/m/Y') }}</td>
                            <td>{{ $prenda->recibo_serie }} {{ $prenda->recibo_numero }}</td>
                            <td>{{ $prenda->orden_cobro }}</td>
                            <td>{{ $prenda->titular_nombre }}</td>
                            <td>{{ $prenda->titular_cedula }}</td>
                            <td>{{ $prenda->concepto }}</td>
                            <td>{{ $prenda->medioPago->nombre ?? '-' }}</td>
                            <td class="text-right">${{ $prenda->monto_formateado }}</td>
                        </tr>
                        @php $total += $prenda->monto; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="8" class="text-right font-weight-bold">Total Planilla:</td>
                            <td class="text-right font-weight-bold">${{ $planilla->total_formateado }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>


        </div>
    </div>
</div>