<div wire:ignore.self class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Registrar Nuevo Certificado</h5>
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
                        <label for="titular_nombre">Nombre del Titular</label>
                        <input type="text" wire:model.defer="titular_nombre" class="form-control" id="titular_nombre">
                        @error('titular_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titular_apellido">Apellido del Titular</label>
                        <input type="text" wire:model.defer="titular_apellido" class="form-control" id="titular_apellido">
                        @error('titular_apellido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titular_tipo_documento">Tipo de Documento</label>
                        <select wire:model.defer="titular_tipo_documento" class="form-control" id="titular_tipo_documento">
                            <option value="Cédula">Cédula</option>
                            <option value="Cédula Extranjera">Cédula Extranjera</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                        @error('titular_tipo_documento') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="titular_nro_documento">Nro. de Documento</label>
                        <input type="text" wire:model.defer="titular_nro_documento" class="form-control" id="titular_nro_documento">
                        @error('titular_nro_documento') <span class="text-danger">{{ $message }}</span> @enderror
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
            'titular_nombre',
            'titular_apellido',
            'titular_tipo_documento',
            'titular_nro_documento',
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
                } else if (currentElement.id === 'titular_nro_documento') {
                    document.getElementById('saveButton').focus();
                } else if (currentElement.id === 'saveButton') {
                    document.getElementById('saveButton').click();
                }
            }
        });
    });
</script>
@endpush