<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

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
                                <select wire:model="concepto_id" class="form-control">
                                    <option value="">Seleccione un concepto</option>
                                    @foreach ($conceptos as $concepto)
                                        <option value="{{ $concepto->idConcepto }}">{{ $concepto->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('concepto_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Detalle</label>
                                <textarea wire:model="detalle" class="form-control" rows="2"></textarea>
                                @error('detalle')
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
                        @forelse ($movimientosAgrupados as $tipo => $gruposDeConceptos)
                            <h4 class="mt-4 text-{{ $tipo == 'INGRESO' ? 'success' : 'danger' }}">{{ ucfirst(strtolower($tipo)) }}s</h4>
                            @foreach ($gruposDeConceptos as $nombreConcepto => $formasPago)
                                <div class="mt-3 p-3 border rounded">
                                    <h5>{{ $nombreConcepto }}</h5>
                                    @foreach ($formasPago as $formaPago => $data)
                                        <h6 class="mt-2 text-muted">{{ ucfirst(strtolower($formaPago)) }}</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Fecha/Hora</th>
                                                        <th>Detalle</th>
                                                        @if($formaPago != 'EFECTIVO')
                                                            <th>Referencia</th>
                                                        @endif
                                                        <th class="text-right">Monto</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($data['movimientos'] as $movimiento)
                                                        <tr>
                                                            <td>{{ $movimiento->fecha->format('d/m/Y') }} {{ $movimiento->hora }}</td>
                                                            <td>{{ $movimiento->detalle }}</td>
                                                            @if($formaPago != 'EFECTIVO')
                                                                <td><small class="text-muted">{{ $movimiento->referencia }}</small></td>
                                                            @endif
                                                            <td class="text-right">${{ number_format($movimiento->monto, 2) }}</td>
                                                            <td>
                                                                <button wire:click="edit({{ $movimiento->idMovimiento }})" class="btn btn-sm btn-primary">Editar</button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="{{ $formaPago != 'EFECTIVO' ? 3 : 2 }}" class="text-right"><strong>Total:</strong></td>
                                                        <td class="text-right"><strong>${{ number_format($data['total'], 2) }}</strong></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @empty
                            <p>No hay movimientos registrados para la caja actual.</p>
                        @endforelse
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

    @if($modal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Movimiento</h5>
                    <button wire:click="closeModal()" type="button" class="close" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
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
                            <select wire:model="concepto_id" class="form-control">
                                <option value="">Seleccione un concepto</option>
                                @foreach ($conceptos as $concepto)
                                    <option value="{{ $concepto->idConcepto }}">{{ $concepto->nombre }}</option>
                                @endforeach
                            </select>
                            @error('concepto_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Detalle</label>
                            <textarea wire:model="detalle" class="form-control" rows="2"></textarea>
                            @error('detalle')
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button wire:click="closeModal()" type="button" class="btn btn-secondary">Cancelar</button>
                    <button wire:click.prevent="registrarMovimiento()" type="button" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
