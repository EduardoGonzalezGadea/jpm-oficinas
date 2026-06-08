<div>
    @if ($showModal)
    <div class="modal fade show" id="modalRecuperarPago" tabindex="-1" role="dialog"
        aria-labelledby="modalRecuperarPagoLabel" style="display: block;" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalRecuperarPagoLabel">
                        <i class="fas fa-hand-holding-usd mr-2"></i>Recuperar Pago Directo
                    </h5>
                    <button type="button" class="close" wire:click="cerrarModal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <form wire:submit.prevent="saveRecuperarPago">
                        <div class="form-group">
                            <label for="recuperarPagoFecha">Fecha de Recuperación:</label>
                            <input type="date" id="recuperarPagoFecha"
                                class="form-control @error('recuperarPagoData.fecha') is-invalid @enderror"
                                wire:model.defer="recuperarPagoData.fecha">
                            @error('recuperarPagoData.fecha')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="recuperarPagoNumeroIngreso">Número de Ingreso:</label>
                            <input type="text" id="recuperarPagoNumeroIngreso"
                                class="form-control @error('recuperarPagoData.numero_ingreso') is-invalid @enderror"
                                wire:model.defer="recuperarPagoData.numero_ingreso"
                                placeholder="Ingrese número de ingreso">
                            @error('recuperarPagoData.numero_ingreso')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        @if ($recuperarPagoData['es_banco_bse'] ?? false)
                        <div class="form-group">
                            <label for="recuperarPagoNumeroIngresoBSE">Número de Ingreso BSE <span class="text-muted">(opcional)</span>:</label>
                            <input type="text" id="recuperarPagoNumeroIngresoBSE"
                                class="form-control @error('recuperarPagoData.numero_ingreso_bse') is-invalid @enderror"
                                wire:model.defer="recuperarPagoData.numero_ingreso_bse"
                                placeholder="Ingrese número de ingreso BSE">
                            @error('recuperarPagoData.numero_ingreso_bse')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="recuperarPagoFechaBSE">Fecha Ingreso BSE <span class="text-muted">(opcional)</span>:</label>
                            <input type="date" id="recuperarPagoFechaBSE"
                                class="form-control @error('recuperarPagoData.fecha_ingreso_bse') is-invalid @enderror"
                                wire:model.defer="recuperarPagoData.fecha_ingreso_bse">
                            @error('recuperarPagoData.fecha_ingreso_bse')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        @endif
                        <div class="form-group">
                            <label for="recuperarPagoMontoRecuperado">Monto Recuperado:</label>
                            <input type="number" id="recuperarPagoMontoRecuperado"
                                class="form-control @error('recuperarPagoData.monto_recuperado') is-invalid @enderror"
                                wire:model.defer="recuperarPagoData.monto_recuperado" step="0.01">
                            @error('recuperarPagoData.monto_recuperado')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-info" wire:click="saveRecuperarPago">
                        <i class="fas fa-save mr-1"></i>Guardar Recuperación
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function() {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('modalRecuperarPago')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-recuperar-pago')[0].cerrarModal();
            }
        });
    });
</script>
@endpush
