<div>
    <div class="row mb-2 align-items-center">
        <div class="col-4">
            @if ($planilla->estado !== 'anulada')
                <button type="button" class="btn btn-danger btn-sm d-print-none" wire:click="anularPlanilla">
                    <i class="fas fa-times mr-1"></i>Anular Planilla
                </button>
            @endif
        </div>
        <div class="col-8 text-right">
            <h4 class="font-weight-bold mb-1">PLANILLA DE CHEQUES N°
                @if(strpos($planilla->numero_planilla, '-') !== false)
                    {{ substr($planilla->numero_planilla, strrpos($planilla->numero_planilla, '-') + 1) }}/{{ $planilla->created_at->format('Y') }}
                @else
                    {{ $planilla->numero_planilla }}/{{ $planilla->created_at->format('Y') }}
                @endif
            </h4>
            <strong>Montevideo, {{ $planilla->created_at->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</strong>
        </div>
    </div>

    @if ($planilla->estado === 'anulada')
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>PLANILLA ANULADA</strong> - Esta planilla ha sido anulada el
            {{ \Carbon\Carbon::parse($planilla->fecha_anulacion)->format('d/m/Y H:i') }}
            @if($planilla->motivo_anulacion)
                <br><strong>Motivo:</strong> {{ $planilla->motivo_anulacion }}
            @endif
        </div>
    @endif

    @if ($planilla->cheques && $planilla->cheques->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th class="align-middle">Serie</th>
                        <th class="align-middle text-nowrap">N° Cheque</th>
                        <th class="align-middle">Banco</th>
                        <th class="align-middle">Cuenta</th>
                        <th class="align-middle">Fecha Emisión</th>
                        <th class="align-middle">Beneficiario</th>
                        <th class="align-middle">Monto</th>
                        <th class="align-middle">Concepto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($planilla->cheques as $cheque)
                        <tr>
                            <td class="align-middle">{{ $cheque->serie }}</td>
                            <td class="align-middle">{{ $cheque->numero_cheque }}</td>
                            <td class="align-middle">{{ $cheque->cuentaBancaria->banco->nombre }}</td>
                            <td class="align-middle">{{ $cheque->cuentaBancaria->numero_cuenta }}</td>
                            <td class="align-middle">
                                {{ $cheque->fecha_emision ? $cheque->fecha_emision->format('d/m/Y') : '-' }}</td>
                            <td class="align-middle">{{ $cheque->beneficiario ?: '-' }}</td>
                            <td class="align-middle">
                                {{ $cheque->monto ? '$' . number_format($cheque->monto, 2, ',', '.') : '-' }}</td>
                            <td class="align-middle">{{ $cheque->concepto ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="6" class="text-right align-middle">TOTAL</td>
                        <td class="text-right align-middle">
                            ${{ number_format($planilla->cheques->sum('monto'), 2, ',', '.') }}</td>
                        <td class="align-middle"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>No se encontraron cheques en esta planilla.
        </div>
    @endif
</div>
