<div>
    <div class="modal fade @if($showModal) show d-block @endif" id="modalRendirPago" tabindex="-1" role="dialog" aria-labelledby="modalRendirPagoLabel" aria-hidden="{{ $showModal ? 'false' : 'true' }}" style="{{ $showModal ? 'background-color: rgba(0,0,0,0.5);' : 'display: none;' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalRendirPagoLabel"><i class="fas fa-file-invoice-dollar mt-1"></i> Rendir Pago Directo</h5>
                    <button type="button" class="close text-white" wire:click="cerrarModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <form wire:submit.prevent="saveRendicionPago">
                    <div class="modal-body pb-0">
                        <!-- Detalles Informativos -->
                        <div class="card mb-3 pb-0" style="background-color: #f8f9fa;">
                            <div class="card-body py-2">
                                <div class="row text-center mt-1">
                                    <div class="col-4">
                                        <p class="mb-1 text-muted small"><strong>Monto Otorgado</strong></p>
                                        <h5 class="font-weight-bold text-dark">${{ number_format($rendirPagoData['monto_original'], 2, ',', '.') }}</h5>
                                    </div>
                                    <div class="col-4">
                                        <p class="mb-1 text-muted small"><strong>Recuperado</strong></p>
                                        <h5 class="font-weight-bold text-success">${{ number_format($rendirPagoData['monto_recuperado_momento'], 2, ',', '.') }}</h5>
                                    </div>
                                    <div class="col-4">
                                        <p class="mb-1 text-muted small"><strong>Saldo Actual</strong></p>
                                        <h5 class="font-weight-bold text-danger">${{ number_format($rendirPagoData['saldo_actual'], 2, ',', '.') }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2" role="alert" style="font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> Ingrese el monto efectivamente gastado (rendido). El reintegrado y extra se calculan automáticamente.
                        </div>

                        <div class="row px-2">
                            <!-- Fecha de Rendición -->
                            <div class="form-group col-md-3 mb-2">
                                <label for="fecha_rendicion" class="font-weight-bold mb-1 small">Fecha Rendición <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="fecha_rendicion" wire:model.defer="rendirPagoData.fecha_rendicion" required>
                                @error('rendirPagoData.fecha_rendicion') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-md-3 mb-2">
                                <label for="monto_rendido" class="font-weight-bold mb-1 small">Monto Rendido <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control" id="monto_rendido" wire:model="rendirPagoData.monto_rendido" step="0.01" min="0" required>
                                </div>
                                @error('rendirPagoData.monto_rendido') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-md-3 mb-2">
                                <label for="monto_reintegrado" class="font-weight-bold mb-1 small">Monto Reintegrado</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                    <input type="number" class="form-control bg-light" id="monto_reintegrado" wire:model="rendirPagoData.monto_reintegrado" step="0.01" min="0" readonly tabindex="-1">
                                </div>
                                @error('rendirPagoData.monto_reintegrado') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-md-3 mb-2">
                                <label for="ingreso_reintegro" class="font-weight-bold mb-1 small">Ingreso Reintegro</label>
                                <input type="text" class="form-control form-control-sm" id="ingreso_reintegro" wire:model.defer="rendirPagoData.ingreso_reintegro" placeholder="Nro. Ingreso">
                                @error('rendirPagoData.ingreso_reintegro') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Extra (solo visible si > 0) -->
                        @if (($rendirPagoData['monto_extra'] ?? 0) > 0)
                        <div class="alert alert-warning py-2 mb-2" role="alert" style="font-size: 0.9rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Extra:</strong> ${{ number_format($rendirPagoData['monto_extra'], 2, ',', '.') }}
                            <small class="d-block mt-1">El monto rendido supera el monto otorgado. La diferencia se registra como extra.</small>
                        </div>
                        @endif
                    </div>
                    
                    <div class="modal-footer pb-0 bg-transparent" style="display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: -5px;">
                        <button type="button" class="btn btn-secondary py-1" wire:click="cerrarModal" style="flex: auto; margin-right: 0 !important; margin-left: 0 !important; border-radius: 6px;">Cancelar</button>
                        <button type="submit" class="btn btn-info py-1" style="flex: auto; margin-right: 0 !important; margin-left: 0 !important; border-radius: 6px;" wire:loading.attr="disabled">
                            <span wire:loading wire:target="saveRendicionPago" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <i class="fas fa-save mt-1" wire:loading.remove wire:target="saveRendicionPago"></i> Guardar Rendición
                        </button>
                    </div>
                    <div class="modal-body p-0 pt-1 text-center font-weight-bold">
                    </div>
                    <div style="height: 10px;"></div>
                </form>
            </div>
        </div>
    </div>
    @if($showModal)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
