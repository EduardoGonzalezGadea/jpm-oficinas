<div>
    @if ($caja_actual)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Arqueo de Caja</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Conteo de Efectivo</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Denominación</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (\App\Models\Tesoreria\Cajas\Denominacion::where('activo', true)->orderBy('valor', 'desc')->get() as $den)
                                        <tr>
                                            <td>${{ number_format($den->valor, 2) }} ({{ $den->tipo }})</td>
                                            <td>
                                                <input type="number"
                                                    wire:model="desglose.{{ $den->idDenominacion }}.cantidad"
                                                    class="form-control form-control-sm" min="0">
                                            </td>
                                            <td>${{ number_format((int)($desglose[$den->idDenominacion]['cantidad'] ?? 0) * $den->valor, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total Efectivo</th>
                                        <th>${{ number_format($total_efectivo, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="mb-3">Otros Medios de Pago</h6>

                        <div class="form-group">
                            <label>Total en Transferencias</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" wire:model="total_transferencias" class="form-control" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Total en Cheques</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" wire:model="total_cheques" class="form-control" readonly>
                            </div>
                        </div>

                        <div
                            class="alert {{ $diferencia == 0 ? 'alert-success' : ($diferencia > 0 ? 'alert-info' : 'alert-danger') }}">
                            <h6 class="alert-heading">Balance</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>Saldo Inicial:</td>
                                    <td class="text-right">${{ number_format($caja_actual->saldo_inicial, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Ingresos:</td>
                                    <td class="text-right">
                                        ${{ number_format($caja_actual->movimientos()->where('tipo_movimiento', 'INGRESO')->sum('monto'), 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total Egresos:</td>
                                    <td class="text-right">
                                        ${{ number_format($caja_actual->movimientos()->where('tipo_movimiento', 'EGRESO')->sum('monto'), 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total en Caja:</td>
                                    <td class="text-right">
                                        ${{ number_format($total_efectivo + $total_transferencias + $total_cheques, 2) }}
                                    </td>
                                </tr>
                                <tr class="font-weight-bold">
                                    <td>Diferencia:</td>
                                    <td class="text-right">${{ number_format($diferencia, 2) }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="form-group">
                            <label>Observaciones</label>
                            <textarea wire:model="observaciones" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="button" wire:click="guardarArqueo" class="btn btn-primary">
                            Guardar Arqueo
                        </button>
                    </div>
                </div>

                @if ($arqueos_previos->isNotEmpty())
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Últimos Arqueos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha/Hora</th>
                                            <th>Efectivo</th>
                                            <th>Otros Medios</th>
                                            <th>Diferencia</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($arqueos_previos as $arqueo)
                                            <tr>
                                                <td>{{ $arqueo->fecha->format('d/m/Y') }} {{ $arqueo->hora }}</td>
                                                <td>${{ number_format($arqueo->total_efectivo, 2) }}</td>
                                                <td>${{ number_format($arqueo->total_transferencias + $arqueo->total_cheques, 2) }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="text-{{ $arqueo->diferencia == 0 ? 'success' : ($arqueo->diferencia > 0 ? 'info' : 'danger') }}">
                                                        ${{ number_format($arqueo->diferencia, 2) }}
                                                    </span>
                                                </td>
                                                <td>{{ $arqueo->usuarioRegistro->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            No hay una caja abierta para realizar el arqueo.
        </div>
    @endif
</div>
