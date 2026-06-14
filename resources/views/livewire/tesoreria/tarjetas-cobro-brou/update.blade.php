<div>
    {{-- Deliver Modal --}}
    <div wire:ignore.self class="modal fade" id="deliverModal" tabindex="-1" role="dialog" aria-labelledby="deliverModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deliverModalLabel">Entregar Tarjeta de Cobro BROU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($tarjeta)
                    <p><strong>Titular:</strong> {{ $tarjeta->titular_nombre }} {{ $tarjeta->titular_apellido }}</p>
                    <p><strong>Nro. Tarjeta:</strong> {{ $tarjeta->numero_tarjeta }}</p>
                    <hr>
                    @endif
                    <form wire:submit.prevent="deliver">
                        <div class="form-group">
                            <label for="deliver_observaciones">Observaciones</label>
                            <textarea wire:model.defer="observaciones" class="form-control" id="deliver_observaciones" rows="3"></textarea>
                            @error('observaciones') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="deliver()">Confirmar Entrega</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Return Modal --}}
    <div wire:ignore.self class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnModalLabel">Devolver Tarjeta de Cobro BROU</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($tarjeta)
                    <p><strong>Titular:</strong> {{ $tarjeta->titular_nombre }} {{ $tarjeta->titular_apellido }}</p>
                    <p><strong>Nro. Tarjeta:</strong> {{ $tarjeta->numero_tarjeta }}</p>
                    <hr>
                    @endif
                    <p>¿Está seguro de que desea marcar esta tarjeta como devuelta?</p>
                    <form wire:submit.prevent="return">
                        <div class="form-group">
                            <label for="return_observaciones">Observaciones</label>
                            <textarea wire:model.defer="observaciones" class="form-control" id="return_observaciones" rows="3"></textarea>
                            @error('observaciones') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" wire:click.prevent="return()">Confirmar Devolución</button>
                </div>
            </div>
        </div>
    </div>
</div>