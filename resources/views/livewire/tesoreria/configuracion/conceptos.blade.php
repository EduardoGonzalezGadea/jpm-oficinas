<div>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list-alt mr-2 text-primary"></i>Gestión de Conceptos de Caja</h5>
            <button class="btn btn-primary" wire:click="create">
                <i class="fas fa-plus mr-1"></i>Nuevo Concepto
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Buscar concepto o código SIIF..." wire:model="search">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered align-middle-cells">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Código SIIF</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">¿Art. 222?</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conceptos as $c)
                        <tr>
                            <td class="font-weight-bold">{{ $c->nombre }}</td>
                            <td>{{ $c->codigo_siif ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge badge-{{ $c->tipo === 'Ingreso' ? 'success' : ($c->tipo === 'Egreso' ? 'danger' : 'info') }}">
                                    {{ $c->tipo }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($c->requiere_institucion)
                                <span class="badge badge-primary">SÍ</span>
                                @else
                                <span class="badge badge-secondary">NO</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $c->activo ? 'success' : 'secondary' }}">
                                    {{ $c->activo ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm py-0" wire:click="edit({{ $c->id }})" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm py-0" wire:click="confirmDelete({{ $c->id }})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No se encontraron conceptos.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $conceptos->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Store/Update --}}
    <div class="modal fade" id="modalConcepto" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">
                        {{ $concepto_id ? 'Editar Concepto' : 'Nuevo Concepto' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form wire:submit.prevent="store">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                wire:model.defer="nombre" placeholder="Nombre completo del concepto">
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Código SIIF</label>
                                    <input type="text" class="form-control" wire:model.defer="codigo_siif" placeholder="Opcional">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Tipo <span class="text-danger">*</span></label>
                                    <select class="form-control" wire:model.defer="tipo">
                                        <option value="Ingreso">Ingreso</option>
                                        <option value="Egreso">Egreso</option>
                                        <option value="Ambos">Ambos</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="swRequiereInst" wire:model.defer="requiere_institucion">
                                    <label class="custom-control-label font-weight-bold" for="swRequiereInst">¿Requiere institución (Art. 222)?</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="swActivo" wire:model.defer="activo">
                                    <label class="custom-control-label font-weight-bold" for="swActivo">Activo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer text-right">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="fas fa-save mr-1"></i>Guardar</span>
                            <span wire:loading><span class="spinner-border spinner-border-sm"></span> Guardando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>