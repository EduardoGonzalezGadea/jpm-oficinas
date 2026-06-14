<div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Tarjeta de Cobro BROU</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="update">
                    <div class="form-group">
                        <label for="edit_fecha_recibido">Fecha de Recepción</label>
                        <input type="date" wire:model.defer="fecha_recibido" class="form-control" id="edit_fecha_recibido">
                        @error('fecha_recibido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_titular_cedula">Cédula del Titular</label>
                        <input type="text" wire:model.defer="titular_cedula" class="form-control" id="edit_titular_cedula">
                        @error('titular_cedula') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_titular_nombre">Nombres del Titular</label>
                        <input type="text" wire:model.defer="titular_nombre" class="form-control" id="edit_titular_nombre">
                        @error('titular_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_titular_apellido">Apellidos del Titular</label>
                        <input type="text" wire:model.defer="titular_apellido" class="form-control" id="edit_titular_apellido">
                        @error('titular_apellido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_numero_tarjeta">Número de Tarjeta</label>
                        <input type="text" wire:model.defer="numero_tarjeta" class="form-control" id="edit_numero_tarjeta">
                        @error('numero_tarjeta') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_observaciones">Observaciones</label>
                        <textarea wire:model.defer="observaciones" class="form-control" id="edit_observaciones" rows="3"></textarea>
                        @error('observaciones') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="updateButton" wire:click.prevent="update()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editModal');
        const formElements = [
            'edit_fecha_recibido',
            'edit_titular_cedula',
            'edit_titular_nombre',
            'edit_titular_apellido',
            'edit_numero_tarjeta',
            'edit_observaciones',
            'updateButton'
        ];

        $(editModal).on('shown.bs.modal', function () {
            document.getElementById('edit_fecha_recibido').focus();
        });

        editModal.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const currentElement = e.target;
                const currentIndex = formElements.indexOf(currentElement.id);

                if (currentIndex > -1 && currentIndex < formElements.length - 1) {
                    const nextElement = document.getElementById(formElements[currentIndex + 1]);
                    if (nextElement) {
                        nextElement.focus();
                    }
                } else if (currentElement.id === 'edit_observaciones') {
                    document.getElementById('updateButton').focus();
                } else if (currentElement.id === 'updateButton') {
                    document.getElementById('updateButton').click();
                }
            }
        });
    });
</script>
@endpush