<div>
    <!-- Modal para editar el detalle del pendiente -->
    <div wire:ignore.self class="modal fade" id="modalEditarDetalle" tabindex="-1" role="dialog" aria-labelledby="modalEditarDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarDetalleLabel">Modificar datos del Pendiente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formEditarPendiente">
                        {{-- Fila 1: Número y Dependencia --}}
                        <div class="row">
                            <!-- Columna Izquierda - Número -->
                            <div class="col-md-6">
                                {{-- Grupo de campo para "Número" --}}
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">Número</span>
                                        <input type="number" wire:model.defer="nroPendiente"
                                            id="inputNumeroPendiente"
                                            class="form-control"
                                            placeholder="Ingrese el número">
                                    </div>
                                    @error('nroPendiente')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Columna Derecha - Dependencia -->
                            <div class="col-md-6">
                                {{-- Grupo de campo para "Dependencia" --}}
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">Dependencia</span>
                                        <select wire:model.defer="relDependencia"
                                            id="selectDependencia"
                                            class="form-control">
                                            <option value="">Seleccione una dependencia</option>
                                            @foreach ($dependencias as $dependencia)
                                            <option value="{{ $dependencia->idDependencias }}">{{ $dependencia->dependencia }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('relDependencia')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Fila 2: Fecha y Monto --}}
                        <div class="row">
                            <!-- Columna Izquierda - Fecha -->
                            <div class="col-md-6">
                                {{-- Grupo de campo para "Fecha" --}}
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">Fecha</span>
                                        <input type="date" wire:model.defer="fechaPendientes"
                                            id="inputFechaPendientes"
                                            class="form-control"
                                            placeholder="Seleccione la fecha">
                                    </div>
                                    @error('fechaPendientes')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Columna Derecha - Monto -->
                            <div class="col-md-6">
                                {{-- Grupo de campo para "Monto" --}}
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">Monto en $</span>
                                        <input type="number" step="1.00" min="0.0" wire:model.defer="montoPendientes"
                                            id="inputMontoPendientes"
                                            class="form-control"
                                            placeholder="Ingrese el monto">
                                    </div>
                                    @error('montoPendientes')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" wire:click.prevent="guardarCambios()" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>
</div>

