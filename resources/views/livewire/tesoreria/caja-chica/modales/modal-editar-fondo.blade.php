<div>
    @if ($showModal)
    <div class="modal fade show" id="modalEditarFondo" tabindex="-1" role="dialog"
        aria-labelledby="modalEditarFondoLabel" style="display: block;" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-animate-in">
                <div class="modal-header modal-header-premium shadow-sm">
                    <h5 class="modal-title" id="modalEditarFondoLabel">
                        <i class="fas fa-edit mr-3"></i>Editar Fondo Permanente
                    </h5>
                    <button type="button" class="close" wire:click="cerrarModal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="actualizarFondo">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="editMes" class="form-label">Mes:</label>
                                <input type="text" class="form-control" id="editMes"
                                    value="{{ $editandoFondo['mes'] }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="editAnio" class="form-label">Año:</label>
                                <input type="text" class="form-control" id="editAnio"
                                    value="{{ $editandoFondo['anio'] }}" readonly>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="editMonto" class="form-label">Monto: <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number"
                                        class="form-control @error('editandoFondo.monto') is-invalid @enderror"
                                        id="editMonto" wire:model="editandoFondo.monto" step="0.01"
                                        min="0" max="99999999.99" placeholder="Ingrese el nuevo monto">
                                </div>
                                @error('editandoFondo.monto')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Monto original:
                                    ${{ number_format((float)$editandoFondo['montoOriginal'], 2, ',', '.') }}
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" wire:click="cerrarModal">
                        <i class="fas fa-arrow-left mr-2"></i>Volver
                    </button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" wire:click="actualizarFondo">
                        <i class="fas fa-check-circle mr-2"></i>Actualizar Fondo
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
        Livewire.on('modal-edit-fondo-opened', function() {
            setTimeout(() => {
                const editMonto = document.getElementById('editMonto');
                if(editMonto) {
                    editMonto.focus();
                    editMonto.select();
                }
            }, 300);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('modalEditarFondo')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-editar-fondo')[0].cerrarModal();
            }
        });
    });
</script>
@endpush
