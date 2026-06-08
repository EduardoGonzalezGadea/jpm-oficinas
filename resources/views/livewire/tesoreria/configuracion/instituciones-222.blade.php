<div>
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-building mr-2"></i>Instituciones Art. 222</h5>
            <button class="btn btn-sm btn-success" wire:click="create">
                <i class="fas fa-plus mr-1"></i> Nueva Institución
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Buscar por nombre o SIIF..." wire:model.debounce.300ms="search">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Nº</th>
                            <th>Nombre</th>
                            <th>Código SIIF</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($instituciones as $inst)
                        <tr>
                            <td>{{ $inst->id }}</td>
                            <td>{{ $inst->nombre }}</td>
                            <td>{{ $inst->codigo_siif ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $inst->activo ? 'success' : 'danger' }}">
                                    {{ $inst->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary py-0" wire:click="edit({{ $inst->id }})" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger py-0" wire:click="confirmDelete({{ $inst->id }})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">No se encontraron instituciones.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $instituciones->links() }}
            </div>
        </div>
    </div>

    {{-- Modal CRUD --}}
    <div class="modal fade" id="modalInstitucion" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-{{ $institucion_id ? 'edit' : 'plus-circle' }} mr-2"></i>
                        {{ $institucion_id ? 'Editar' : 'Nueva' }} Institución Art. 222
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="store">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" wire:model.defer="nombre" placeholder="Ej: ANTEL">
                            @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">Código SIIF (Opcional)</label>
                            <input type="text" class="form-control @error('codigo_siif') is-invalid @enderror" wire:model.defer="codigo_siif" placeholder="Ej: 9051">
                            @error('codigo_siif') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="activoSwitch" wire:model.defer="activo">
                            <label class="custom-control-label font-weight-bold" for="activoSwitch">Institución Activa</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="store" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="store"><i class="fas fa-save mr-1"></i>Guardar</span>
                        <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm mr-1"></span>Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>