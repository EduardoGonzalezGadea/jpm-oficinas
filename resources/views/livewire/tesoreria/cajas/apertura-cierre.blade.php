<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        {{-- Sección de Apertura de Caja --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        @if ($cajaAbierta)
                            Caja Actual
                        @else
                            Apertura de Caja
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if (!$cajaAbierta)
                        <form wire:submit.prevent="abrirCaja">
                            <div class="form-group">
                                <label for="fecha_apertura">Fecha de Apertura</label>
                                <input type="date" wire:model="fecha_apertura" class="form-control"
                                    id="fecha_apertura">
                                @error('fecha_apertura')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group mb-4">
                                <label>Modo de Cálculo</label>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                    <label
                                        class="btn btn-outline-primary @if ($modo_calculo === 'cantidad') active @endif">
                                        <input type="radio" wire:model="modo_calculo" value="cantidad"> Por Cantidad
                                    </label>
                                    <label
                                        class="btn btn-outline-primary @if ($modo_calculo === 'total') active @endif">
                                        <input type="radio" wire:model="modo_calculo" value="total"> Por Total
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <h6>Desglose Monetario Inicial</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Denominación</th>
                                                <th>Cantidad</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($denominaciones as $denominacion)
                                                <tr>
                                                    <td>${{ number_format($denominacion->valor, 2) }}
                                                        ({{ $denominacion->tipo }})
                                                    </td>
                                                    <td style="width: 120px;">
                                                        <input type="number" class="form-control form-control-sm"
                                                            wire:model.lazy="desglose.{{ $denominacion->idDenominacion }}.cantidad"
                                                            @if ($modo_calculo === 'total') readonly @endif min="0">
                                                    </td>
                                                    <td style="width: 120px;">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">$</span>
                                                            </div>
                                                            <input type="number" class="form-control"
                                                                wire:model.lazy="desglose.{{ $denominacion->idDenominacion }}.total"
                                                                @if ($modo_calculo === 'cantidad') readonly @endif
                                                                min="0" step="{{ $denominacion->valor }}">
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2" class="text-right"><strong>Total:</strong></td>
                                                <td><strong>${{ number_format($saldo_inicial, 2) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea wire:model="observaciones" id="observaciones" class="form-control" rows="2"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Abrir Caja
                            </button>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <h6>Información de la Caja Actual:</h6>
                            <p class="mb-1">Fecha de Apertura: {{ $cajaAbierta->fecha_apertura->format('d/m/Y') }}
                            </p>
                            <p class="mb-1">Hora: {{ $cajaAbierta->hora_apertura }}</p>
                            <p class="mb-1">Saldo Inicial: ${{ number_format($cajaAbierta->saldo_inicial, 2) }}</p>
                            <p class="mb-0">Usuario: {{ $cajaAbierta->usuarioApertura->name ?? 'No asignado' }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sección de Cierre de Caja --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Cierre de Caja</h5>
                </div>
                <div class="card-body">
                    @if ($cajaAbierta)
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Saldo Inicial
                                <span>${{ number_format($cajaAbierta->saldo_inicial, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total Entradas
                                <span>${{ number_format($cajaAbierta->movimientos()->where('tipo_movimiento', 'INGRESO')->sum('monto'), 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total Salidas
                                <span>${{ number_format($cajaAbierta->movimientos()->where('tipo_movimiento', 'EGRESO')->sum('monto'), 2) }}</span>
                            </li>
                            <li
                                class="list-group-item d-flex justify-content-between align-items-center font-weight-bold">
                                Saldo Final Esperado
                                <span>${{ number_format($cajaAbierta->obtenerSaldoActual(), 2) }}</span>
                            </li>
                        </ul>

                        <button wire:click="cerrarCaja" class="btn btn-danger"
                            onclick="return confirm('¿Está seguro de cerrar la caja?')">
                            Cerrar Caja
                        </button>
                    @else
                        <div class="alert alert-warning">
                            No hay una caja abierta en este momento.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
