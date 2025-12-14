<div wire:ignore.self class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Nuevo Depósito de Vehículo</h5>
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
                                <label for="titular">Titular</label>
                                <input type="text" wire:model.defer="titular" class="form-control" id="titular">
                                @error('titular') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="cedula">Cédula</label>
                                <input type="text" wire:model.defer="cedula" class="form-control" id="cedula">
                                @error('cedula') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="text" wire:model.defer="telefono" class="form-control" id="telefono">
                                @error('telefono') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monto">Monto</label>
                                <input type="number" step="0.01" wire:model.defer="monto" class="form-control" id="monto">
                                @error('monto') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
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
