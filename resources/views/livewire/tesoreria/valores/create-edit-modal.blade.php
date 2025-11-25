    {{-- Modal Crear/Editar --}}
    <div class="modal fade" id="createEditModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $showCreateModal ? 'Crear Nuevo Valor' : 'Editar Valor' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label>Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                       wire:model="nombre" placeholder="Ej: Recibos de Agua">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label>Recibos por Libreta <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('recibos') is-invalid @enderror"
                                       wire:model="recibos" placeholder="100" min="1">
                                @error('recibos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tipo de Valor <span class="text-danger">*</span></label>
                                <select class="form-control @error('tipo_valor') is-invalid @enderror" wire:model="tipo_valor">
                                    <option value="pesos">Pesos</option>
                                    <option value="UI">Unidad Indexada</option>
                                    <option value="SVE">Sin Valor Escrito</option>
                                </select>
                                @error('tipo_valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Valor
                                    @if($tipo_valor !== 'SVE') <span class="text-danger">*</span> @endif
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control @error('valor') is-invalid @enderror"
                                           wire:model="valor" placeholder="0.00"
                                           @if($tipo_valor === 'SVE') disabled @endif>
                                </div>
                                @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if($tipo_valor === 'SVE')
                                    <small class="text-muted">El valor no aplica para "Sin Valor Escrito"</small>
                                @endif
                            </div>
                            <div class="form-group col-12">
                                <label>Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                          wire:model="descripcion" rows="3"
                                          placeholder="Descripción opcional del valor..."></textarea>
                                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="activo" id="activo">
                                    <label class="form-check-label" for="activo">
                                        Activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    @if($showCreateModal)
                        <button type="button" class="btn btn-primary" wire:click="create">
                            <i class="fas fa-save mr-2"></i>Crear Valor
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="update">
                            <i class="fas fa-save mr-2"></i>Actualizar Valor
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
