<div>
    @if ($showModal)
    <div class="modal fade show" id="modalRecuperarRendido" tabindex="-1" role="dialog"
        aria-labelledby="modalRecuperarRendidoLabel" style="display: block;" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalRecuperarRendidoLabel">
                        <i class="fas fa-hand-holding-usd mr-2"></i>Recuperar Dinero Rendido de Pendiente
                    </h5>
                    <button type="button" class="close" wire:click="cerrarModal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <form wire:submit.prevent="saveRecuperarRendido">
                        <div class="form-group">
                            <label for="recuperarRendidoFecha">Fecha:</label>
                            <input type="date" id="recuperarRendidoFecha"
                                class="form-control @error('recuperarRendidoData.fecha') is-invalid @enderror"
                                wire:model.defer="recuperarRendidoData.fecha">
                            @error('recuperarRendidoData.fecha')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="recuperarRendidoDocumentos">Documentos:</label>
                            <input type="text" id="recuperarRendidoDocumentos"
                                class="form-control @error('recuperarRendidoData.documentos') is-invalid @enderror"
                                wire:model.defer="recuperarRendidoData.documentos"
                                placeholder="Ingrese documentos">
                            @error('recuperarRendidoData.documentos')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="recuperarRendidoMontoRecuperado">Monto Recuperado:</label>
                            <input type="number" id="recuperarRendidoMontoRecuperado"
                                class="form-control @error('recuperarRendidoData.monto_recuperado') is-invalid @enderror"
                                wire:model.defer="recuperarRendidoData.monto_recuperado" step="0.01">
                            @error('recuperarRendidoData.monto_recuperado')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-info" wire:click="saveRecuperarRendido">
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
            if (e.key === 'Escape' && document.getElementById('modalRecuperarRendido')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-recuperar-rendido')[0].cerrarModal();
            }
        });
    });
</script>
@endpush
