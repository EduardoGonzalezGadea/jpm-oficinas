<div wire:ignore.self class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Registrar Nueva Tarjeta de Cobro BROU</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="save">
                    <div class="form-group">
                        <label for="fecha_recibido">Fecha de Recepción</label>
                        <input type="date" wire:model.defer="fecha_recibido" class="form-control" id="fecha_recibido">
                        @error('fecha_recibido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titular_cedula">Cédula del Titular</label>
                        <input type="text" wire:model.defer="titular_cedula" class="form-control" id="titular_cedula" placeholder="Ej: 1.234.567-8">
                        @error('titular_cedula') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titular_nombre">Nombres del Titular</label>
                        <input type="text" wire:model.defer="titular_nombre" class="form-control" id="titular_nombre">
                        @error('titular_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titular_apellido">Apellidos del Titular</label>
                        <input type="text" wire:model.defer="titular_apellido" class="form-control" id="titular_apellido">
                        @error('titular_apellido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="numero_tarjeta">Número de Tarjeta</label>
                        <input type="text" wire:model.defer="numero_tarjeta" class="form-control" id="numero_tarjeta">
                        @error('numero_tarjeta') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea wire:model.defer="observaciones" class="form-control" id="observaciones" rows="3"></textarea>
                        @error('observaciones') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveButton" wire:click.prevent="save()">Guardar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const createModal = document.getElementById('createModal');
        const formElements = [
            'fecha_recibido',
            'titular_cedula',
            'titular_nombre',
            'titular_apellido',
            'numero_tarjeta',
            'observaciones',
            'saveButton'
        ];

        $(createModal).on('shown.bs.modal', function () {
            document.getElementById('fecha_recibido').focus();
        });

        createModal.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const currentElement = e.target;
                const currentIndex = formElements.indexOf(currentElement.id);

                if (currentIndex > -1 && currentIndex < formElements.length - 1) {
                    const nextElement = document.getElementById(formElements[currentIndex + 1]);
                    if (nextElement) {
                        nextElement.focus();
                    }
                } else if (currentElement.id === 'observaciones') {
                    document.getElementById('saveButton').focus();
                } else if (currentElement.id === 'saveButton') {
                    document.getElementById('saveButton').click();
                }
            }
        });
    });
</script>
@endpush