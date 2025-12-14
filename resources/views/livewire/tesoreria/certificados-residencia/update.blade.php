<div>
    {{-- Deliver Modal --}}
    <div wire:ignore.self class="modal fade" id="deliverModal" tabindex="-1" role="dialog" aria-labelledby="deliverModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deliverModalLabel">Entregar Certificado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="deliver">
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="retiraEsTitular" wire:model="retiraEsTitular">
                            <label class="form-check-label" for="retiraEsTitular">Quien retira es el titular</label>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="retira_nombre">Nombre de quien retira</label>
                            <input type="text" wire:model.defer="retira_nombre" class="form-control" id="retira_nombre">
                            @error('retira_nombre') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="retira_apellido">Apellido de quien retira</label>
                            <input type="text" wire:model.defer="retira_apellido" class="form-control" id="retira_apellido">
                            @error('retira_apellido') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="retira_tipo_documento">Tipo de Documento</label>
                            <select wire:model.defer="retira_tipo_documento" class="form-control" id="retira_tipo_documento">
                                <option value="Cédula">Cédula</option>
                                <option value="Cédula Extranjera">Cédula Extranjera</option>
                                <option value="Pasaporte">Pasaporte</option>
                                <option value="Otro">Otro</option>
                            </select>
                            @error('retira_tipo_documento') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="retira_nro_documento">Nro. de Documento</label>
                            <input type="text" wire:model.defer="retira_nro_documento" class="form-control" id="retira_nro_documento">
                            @error('retira_nro_documento') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="retira_telefono">Teléfono</label>
                            <input type="text" wire:model.defer="retira_telefono" class="form-control" id="retira_telefono">
                            @error('retira_telefono') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="numero_recibo">Número de Recibo</label>
                            <input type="text" wire:model.defer="numero_recibo" class="form-control" id="numero_recibo">
                            @error('numero_recibo') <span class="text-danger">{{ $message }}</span> @enderror
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
                    <h5 class="modal-title" id="returnModalLabel">Devolver Certificado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea marcar este certificado como devuelto?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning" wire:click.prevent="return()">Confirmar Devolución</button>
                </div>
            </div>
        </div>
    </div>
</div>