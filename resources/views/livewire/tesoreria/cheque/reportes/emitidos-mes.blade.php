@if($reporteMes && $reporteAnio)
    @if($chequesEmitidosMes->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-paper-plane mr-2"></i>Cheques Emitidos - {{ \Carbon\Carbon::create()->month((int)$reporteMes)->locale('es')->monthName }} {{ $reporteAnio }}
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr class="text-center">
                                <th class="align-middle">Serie</th>
                                <th class="align-middle">N° Cheque</th>
                                <th class="align-middle">Banco</th>
                                <th class="align-middle">Cuenta</th>
                                <th class="align-middle">Beneficiario</th>
                                <th class="align-middle">Monto</th>
                                <th class="align-middle">Fecha Emisión</th>
                                <th class="align-middle">Concepto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chequesEmitidosMes as $cheque)
                                <tr class="text-center">
                                    <td class="align-middle">{{ $cheque->serie }}</td>
                                    <td class="align-middle">{{ $cheque->numero_cheque }}</td>
                                    <td class="align-middle">{{ $cheque->cuentaBancaria->banco->codigo }}</td>
                                    <td class="align-middle">{{ $cheque->cuentaBancaria->numero_cuenta }}</td>
                                    <td class="align-middle">{{ $cheque->beneficiario ?: '-' }}</td>
                                    <td class="align-middle">
                                        @if($cheque->monto)
                                            ${{ number_format($cheque->monto, 2, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $cheque->fecha_emision ? $cheque->fecha_emision->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="align-middle">
                                        @if($cheque->concepto)
                                            <small>{{ $cheque->concepto }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="5" class="text-right align-middle">TOTAL</td>
                                <td class="text-right align-middle">
                                    ${{ number_format($chequesEmitidosMes->sum('monto'), 2, ',', '.') }}
                                </td>
                                <td colspan="2" class="align-middle"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-3">
                    <strong>Total de cheques emitidos: {{ $chequesEmitidosMes->count() }}</strong>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>No se encontraron cheques emitidos para {{ \Carbon\Carbon::create()->month((int)$reporteMes)->locale('es')->monthName }} de {{ $reporteAnio }}.
        </div>
    @endif
@else
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>Seleccione un mes y año para ver los cheques emitidos.
    </div>
@endif
