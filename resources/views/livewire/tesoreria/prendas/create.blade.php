<div wire:ignore.self class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Nueva Prenda</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="store">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="recibo_serie">Serie Recibo</label>
                                <input type="text" wire:model.defer="recibo_serie" class="form-control" id="recibo_serie">
                                @error('recibo_serie') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="recibo_numero">Número Recibo</label>
                                <input type="text" wire:model.defer="recibo_numero" class="form-control" id="recibo_numero">
                                @error('recibo_numero') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="recibo_fecha">Fecha Recibo</label>
                                <input type="date" wire:model.defer="recibo_fecha" class="form-control" id="recibo_fecha">
                                @error('recibo_fecha') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="orden_cobro">Orden de Cobro</label>
                                <input type="text" wire:model.defer="orden_cobro" class="form-control" id="orden_cobro">
                                @error('orden_cobro') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="concepto">Concepto</label>
                                <input type="text" wire:model.defer="concepto" class="form-control" id="concepto">
                                @error('concepto') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="titular_nombre">Nombre Titular</label>
                                <input type="text" wire:model.defer="titular_nombre" class="form-control" id="titular_nombre">
                                @error('titular_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="titular_cedula">Cédula Titular</label>
                                <input type="text" wire:model.defer="titular_cedula" class="form-control" id="titular_cedula">
                                @error('titular_cedula') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="titular_telefono">Teléfono Titular</label>
                                <input type="text" wire:model.defer="titular_telefono" class="form-control" id="titular_telefono">
                                @error('titular_telefono') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="monto">Monto</label>
                                <input type="number" step="0.01" wire:model.defer="monto" class="form-control" id="monto">
                                @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="medio_pago_id">Medio de Pago</label>
                                <select wire:model.defer="medio_pago_id" class="form-control" id="medio_pago_id">
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
                                <label for="transferencia">Transferencia</label>
                                <input type="text" wire:model.lazy="transferencia" class="form-control" id="transferencia">
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
                                <label for="transferencia_fecha">Fecha Transferencia</label>
                                <input type="date" wire:model.defer="transferencia_fecha" class="form-control" id="transferencia_fecha">
                                @error('transferencia_fecha') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" wire:click.prevent="store">Guardar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('swal:confirm-duplicate-create', function(data) {
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
                    window.livewire.emit('confirmStore');
                }
            });
        });

        // Autofocus al abrir el modal
        $('#createModal').on('shown.bs.modal', function () {
            $('#recibo_serie').trigger('focus');
        });

        // Navegación con Enter
        $('#createModal form').on('keydown', 'input, select', function(e) {
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
                    $('#createModal .btn-primary').focus();
                }
            }
        });
    });
</script>
@endpush
