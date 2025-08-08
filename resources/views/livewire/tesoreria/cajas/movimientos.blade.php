<div>
    @if ($caja_actual)
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Registrar Movimiento</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="registrarMovimiento">
                            <div class="form-group">
                                <label>Tipo de Movimiento</label>
                                <select wire:model="tipo_movimiento" class="form-control">
                                    <option value="INGRESO">Ingreso</option>
                                    <option value="EGRESO">Egreso</option>
                                </select>
                                @error('tipo_movimiento')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Concepto</label>
                                <textarea wire:model="concepto" class="form-control" rows="2"></textarea>
                                @error('concepto')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Monto</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" wire:model="monto" class="form-control" step="0.01">
                                </div>
                                @error('monto')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Forma de Pago</label>
                                <select wire:model="forma_pago" class="form-control">
                                    <option value="EFECTIVO">Efectivo</option>
                                    <option value="TRANSFERENCIA">Transferencia</option>
                                    <option value="CHEQUE">Cheque</option>
                                </select>
                                @error('forma_pago')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            @if ($forma_pago != 'EFECTIVO')
                                <div class="form-group">
                                    <label>Referencia</label>
                                    <input type="text" wire:model="referencia" class="form-control">
                                    @error('referencia')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            <button type="submit" class="btn btn-primary">
                                Registrar Movimiento
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Movimientos de la Caja</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Tipo</th>
                                        <th>Concepto</th>
                                        <th>Forma Pago</th>
                                        <th>Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movimientos as $movimiento)
                                        <tr>
                                            <td>{{ $movimiento->fecha->format('d/m/Y') }} {{ $movimiento->hora }}</td>
                                            <td>
                                                <span
                                                    class="badge badge-{{ $movimiento->tipo_movimiento == 'INGRESO' ? 'success' : 'danger' }}">
                                                    {{ $movimiento->tipo_movimiento }}
                                                </span>
                                            </td>
                                            <td>{{ $movimiento->concepto }}</td>
                                            <td>
                                                {{ $movimiento->forma_pago }}
                                                @if ($movimiento->referencia)
                                                    <br>
                                                    <small class="text-muted">Ref:
                                                        {{ $movimiento->referencia }}</small>
                                                @endif
                                            </td>
                                            <td class="text-right">${{ number_format($movimiento->monto, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $movimientos->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            No hay una caja abierta en este momento. Por favor, abra una caja para registrar movimientos.
        </div>
    @endif

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mt-3">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif
</div>
