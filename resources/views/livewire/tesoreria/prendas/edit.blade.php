<div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Prenda</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="update">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_recibo_serie">Serie Recibo</label>
                                <input type="text" wire:model.defer="recibo_serie" class="form-control" id="edit_recibo_serie">
                                @error('recibo_serie') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_recibo_numero">Número Recibo</label>
                                <input type="text" wire:model.defer="recibo_numero" class="form-control" id="edit_recibo_numero">
                                @error('recibo_numero') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_recibo_fecha">Fecha Recibo</label>
                                <input type="date" wire:model.defer="recibo_fecha" class="form-control" id="edit_recibo_fecha">
                                @error('recibo_fecha') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_orden_cobro">Orden de Cobro</label>
                                <input type="text" wire:model.defer="orden_cobro" class="form-control" id="edit_orden_cobro">
                                @error('orden_cobro') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_concepto">Concepto</label>
                                <input type="text" wire:model.defer="concepto" class="form-control" id="edit_concepto">
                                @error('concepto') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_titular_nombre">Nombre Titular</label>
                                <input type="text" wire:model.defer="titular_nombre" class="form-control" id="edit_titular_nombre">
                                @error('titular_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_titular_cedula">Cédula Titular</label>
                                <input type="text" wire:model.defer="titular_cedula" class="form-control" id="edit_titular_cedula">
                                @error('titular_cedula') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_titular_telefono">Teléfono Titular</label>
                                <input type="text" wire:model.defer="titular_telefono" class="form-control" id="edit_titular_telefono">
                                @error('titular_telefono') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_monto">Monto</label>
                                <input type="number" step="0.01" wire:model.defer="monto" class="form-control" id="edit_monto">
                                @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_medio_pago_id">Medio de Pago</label>
                                <select wire:model.defer="medio_pago_id" class="form-control" id="edit_medio_pago_id">
                                    <option value="">Seleccione...</option>
                                    @foreach($mediosPago as $medio)
                                        <option value="{{ $medio->id }}">{{ $medio->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('medio_pago_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_transferencia">Transferencia</label>
                                <input type="text" wire:model.lazy="transferencia" class="form-control" id="edit_transferencia">
                                @error('transferencia') <span class="text-danger">{{ $message }}</span> @enderror
                                @if($showDuplicateAlert)
                                    <small class="text-warning font-weight-bold">
                                        <i class="fas fa-exclamation-triangle"></i> Transferencia duplicada
                                    </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_transferencia_fecha">Fecha Transferencia</label>
                                <input type="date" wire:model.defer="transferencia_fecha" class="form-control" id="edit_transferencia_fecha">
                                @error('transferencia_fecha') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" wire:click.prevent="update">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('swal:confirm-duplicate-edit', function(data) {
            Swal.fire({
                title: data.title || 'Transferencia Duplicada',
                text: data.text || 'El número de transferencia ya existe. ¿Desea continuar?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.livewire.emit('confirmUpdate');
                }
            });
        });

        // Autofocus al abrir el modal
        $('#editModal').on('shown.bs.modal', function () {
            $('#edit_recibo_serie').trigger('focus');
        });

        // Navegación con Enter
        $('#editModal form').on('keydown', 'input, select', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var self = $(this);
                var form = self.closest('form');
                var focusable = form.find('input, select').filter(':visible:not([readonly]):not([disabled])');
                var next = focusable.eq(focusable.index(this) + 1);
                
                if (next.length) {
                    next.focus();
                } else {
                    // Si es el último campo, enfocar el botón de guardar
                    $('#editModal .btn-primary').focus();
                }
            }
        });
    });
</script>
@endpush
