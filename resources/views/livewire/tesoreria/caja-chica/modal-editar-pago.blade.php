<div>
    @if ($show)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Pago</h5>
                        <button type="button" class="close" wire:click="cerrarModal">
                            <span>&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form wire:submit.prevent="actualizarPago">
                            <div class="form-group">
                                <label>Fecha de Egreso</label>
                                <input type="date" class="form-control" wire:model.defer="pago.fechaEgresoPagos">
                                @error('pago.fechaEgresoPagos')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Nro. de Egreso (Opcional)</label>
                                <input type="text" class="form-control" wire:model.defer="pago.egresoPagos"
                                    placeholder="Número de egreso (opcional)">
                                @error('pago.egresoPagos')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Acreedor</label>
                                <select class="form-control" wire:model.defer="pago.relAcreedores">
                                    <option value="">Seleccione</option>
                                    @foreach ($acreedores as $acreedor)
                                        <option value="{{ $acreedor->idAcreedores }}">{{ $acreedor->acreedor }}</option>
                                    @endforeach
                                </select>
                                @error('pago.relAcreedores')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Concepto</label>
                                <textarea class="form-control" wire:model.defer="pago.conceptoPagos"></textarea>
                                @error('pago.conceptoPagos')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Monto</label>
                                    <input type="number" class="form-control" step="0.01"
                                        wire:model.defer="pago.montoPagos">
                                    @error('pago.montoPagos')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label>Recuperado</label>
                                    <input type="number" class="form-control" step="0.01"
                                        wire:model.defer="pago.recuperadoPagos">
                                    @error('pago.recuperadoPagos')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <hr>

                            <h6>Información del Ingreso (Recuperación)</h6>

                            <div class="form-group">
                                <label>Fecha del Ingreso</label>
                                <input type="date" class="form-control" wire:model.defer="pago.fechaIngresoPagos">
                                @error('pago.fechaIngresoPagos')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Nro. del Ingreso</label>
                                <input type="text" class="form-control" wire:model.defer="pago.ingresoPagos">
                                @error('pago.ingresoPagos')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Nro. del Ingreso BSE</label>
                                <input type="text" class="form-control" wire:model.defer="pago.ingresoPagosBSE">
                                @error('pago.ingresoPagosBSE')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    wire:click="cerrarModal">Cancelar</button>
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    Guardar
                                    <span wire:loading wire:target="actualizarPago"
                                        class="spinner-border spinner-border-sm ml-2"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
