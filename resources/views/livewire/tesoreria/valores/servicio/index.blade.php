<div>
    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">
                <i class="fas fa-cogs mr-2"></i>Servicios para Valores
            </h4>
            <div>
                <a href="{{ route('tesoreria.valores.index') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-arrow-left mr-1"></i>Regresar a Valores
                </a>
                <a href="{{ route('tesoreria.valores.tipos-libreta') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-book mr-1"></i> Tipos
                </a>
                <button wire:click="create()" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Nuevo Servicio
                </button>
            </div>
        </div>
        <div class="card-body p-1">
            <div class="row mb-1">
                <div class="col">
                    <div class="input-group">
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por nombre...">
                        <div class="input-group-append">
                            <button type="button" wire:click="clearSearch" class="btn btn-outline-danger" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th class="align-middle">Nombre</th>
                            <th class="text-center align-middle">Valor UI</th>
                            <th class="text-center align-middle">Estado</th>
                            <th class="text-center align-middle">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servicios as $servicio)
                            <tr>
                                <td class="align-middle">{{ $servicio->nombre }}</td>
                                <td class="text-center align-middle">{{ $servicio->valor_ui ? number_format($servicio->valor_ui, 2, ',', '.') . ' U.I.' : 'S.V.E.' }}</td>
                                <td class="text-center align-middle">
                                    <button wire:click="toggleStatus({{ $servicio->id }})" class="btn btn-sm {{ $servicio->activo ? 'btn-success' : 'btn-danger' }} py-0">
                                        {{ $servicio->activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        <button wire:click="edit({{ $servicio->id }})" class="btn btn-sm btn-warning py-0" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button wire:click="confirmDelete({{ $servicio->id }})" class="btn btn-sm btn-danger py-0" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No se encontraron servicios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $servicios->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $servicioId ? 'Editar' : 'Crear' }} Servicio</h5>
                    <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" wire:model.defer="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" placeholder="Nombre del servicio">
                        @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="valor_ui">Valor UI</label>
                        <input type="number" step="0.01" wire:model.defer="valor_ui" id="valor_ui" class="form-control @error('valor_ui') is-invalid @enderror">
                        @error('valor_ui') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" wire:model.defer="activo" class="custom-control-input" id="activo">
                            <label class="custom-control-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Borrar -->
    @if($showDeleteModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="close" wire:click="closeDeleteModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar este servicio? Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click="destroy()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
