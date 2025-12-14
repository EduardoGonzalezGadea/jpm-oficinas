<div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Certificado</h5>
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
                        <label for="edit_titular_nombre">Nombre del Titular</label>
                        <input type="text" wire:model.defer="titular_nombre" class="form-control" id="edit_titular_nombre">
                        @error('titular_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_titular_apellido">Apellido del Titular</label>
                        <input type="text" wire:model.defer="titular_apellido" class="form-control" id="edit_titular_apellido">
                        @error('titular_apellido') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_titular_tipo_documento">Tipo de Documento</label>
                        <select wire:model.defer="titular_tipo_documento" class="form-control" id="edit_titular_tipo_documento">
                            <option value="Cédula">Cédula</option>
                            <option value="Cédula Extranjera">Cédula Extranjera</option>
                            <option value="Pasaporte">Pasaporte</option>
                            <option value="Otro">Otro</option>
                        </select>
                        @error('titular_tipo_documento') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="edit_titular_nro_documento">Nro. de Documento</label>
                        <input type="text" wire:model.defer="titular_nro_documento" class="form-control" id="edit_titular_nro_documento">
                        @error('titular_nro_documento') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if ($estado == 'Entregado' || $fecha_entregado)
                        <hr>
                        <h5>Datos de Entrega</h5>

                        <div class="form-group">
                            <label for="edit_fecha_entregado">Fecha de Entrega</label>
                            <input type="date" wire:model.defer="fecha_entregado" class="form-control" id="edit_fecha_entregado">
                            @error('fecha_entregado') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="edit_retira_nombre">Nombre de quien retira</label>
                            <input type="text" wire:model.defer="retira_nombre" class="form-control" id="edit_retira_nombre">
                            @error('retira_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="edit_retira_apellido">Apellido de quien retira</label>
                            <input type="text" wire:model.defer="retira_apellido" class="form-control" id="edit_retira_apellido">
                            @error('retira_apellido') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="edit_retira_tipo_documento">Tipo de Documento</label>
                            <select wire:model.defer="retira_tipo_documento" class="form-control" id="edit_retira_tipo_documento">
                                <option value="">Seleccione...</option>
                                <option value="Cédula">Cédula</option>
                                <option value="Cédula Extranjera">Cédula Extranjera</option>
                                <option value="Pasaporte">Pasaporte</option>
                                <option value="Otro">Otro</option>
                            </select>
                            @error('retira_tipo_documento') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="edit_retira_nro_documento">Nro. de Documento</label>
                            <input type="text" wire:model.defer="retira_nro_documento" class="form-control" id="edit_retira_nro_documento">
                            @error('retira_nro_documento') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="edit_numero_recibo">Número de Recibo</label>
                            <input type="text" wire:model.defer="numero_recibo" class="form-control" id="edit_numero_recibo">
                            @error('numero_recibo') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    @endif
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
            'edit_titular_nombre',
            'edit_titular_apellido',
            'edit_titular_tipo_documento',
            'edit_titular_nro_documento',
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
                } else if (currentElement.id === 'edit_titular_nro_documento') {
                    document.getElementById('updateButton').focus();
                } else if (currentElement.id === 'updateButton') {
                    document.getElementById('updateButton').click();
                }
            }
        });
    });
</script>
@endpush