<div wire:ignore.self class="modal fade" id="modalNuevoFondo" tabindex="-1" aria-labelledby="modalNuevoFondoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form wire:submit.prevent="guardarNuevoFondo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoFondoLabel">Nuevo Fondo Permanente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nuevoFondoMes">Mes:</label>
                        <input type="text" class="form-control" id="nuevoFondoMes" wire:model="nuevoFondo.mes" required>
                    </div>
                    <div class="form-group">
                        <label for="nuevoFondoAnio">Año:</label>
                        <input type="number" class="form-control" id="nuevoFondoAnio" wire:model="nuevoFondo.anio" required>
                    </div>
                    <div class="form-group">
                        <label for="nuevoFondoMonto">Monto:</label>
                        <input type="number" step="0.01" class="form-control" id="nuevoFondoMonto" wire:model="nuevoFondo.monto" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>