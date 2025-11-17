<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">
                <i class="fas fa-book mr-2"></i>Gestión de Tipos de Libreta
            </h4>
            <div>
                <a href="{{ route('tesoreria.valores.index') }}" class="btn btn-success mr-2">
                    <i class="fas fa-barcode mr-1"></i> Libretas de Valores
                </a>
                <a href="{{ route('tesoreria.valores.servicios') }}" class="btn btn-success mr-2">
                    <i class="fas fa-cogs mr-1"></i> Servicios
                </a>
                <button wire:click="create()" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Nuevo Tipo de Libreta
                </button>
            </div>
        </div>
        <div class="card-body p-1">
            <div class="row mb-1">
                <div class="col-md-6">
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar por nombre...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th class="align-middle">Nombre</th>
                            <th class="text-center align-middle">Recibos</th>
                            <th class="text-center align-middle">S./Min.</th>
                            <th class="text-center align-middle">Servicios Asociados</th>
                            <th class="text-center align-middle">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tiposLibreta as $tipo)
                            <tr>
                                <td class="align-middle">{{ $tipo->nombre }}</td>
                                <td class="text-center align-middle">{{ $tipo->cantidad_recibos }}</td>
                                <td class="text-center align-middle">{{ $tipo->stock_minimo_recibos }}</td>
                                <td class="align-middle">
                                    @foreach($tipo->servicios as $servicio)
                                        <span class="badge badge-info">{{ $servicio->nombre }}</span>
                                    @endforeach
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        <button wire:click="edit({{ $tipo->id }})" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button wire:click="confirmDelete({{ $tipo->id }})" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No se encontraron tipos de libreta.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $tiposLibreta->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $tipoLibretaId ? 'Editar' : 'Crear' }} Tipo de Libreta</h5>
                    <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" wire:model.defer="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror">
                        @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cantidad_recibos">Cantidad de Recibos</label>
                                <select wire:model.defer="cantidad_recibos" id="cantidad_recibos" class="form-control @error('cantidad_recibos') is-invalid @enderror">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                @error('cantidad_recibos') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stock_minimo_recibos">Stock Mínimo (Recibos)</label>
                                <input type="number" wire:model.defer="stock_minimo_recibos" id="stock_minimo_recibos" class="form-control @error('stock_minimo_recibos') is-invalid @enderror">
                                @error('stock_minimo_recibos') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Servicios Asociados</label>
                        <div class="card" style="max-height: 200px; overflow-y: auto;">
                            <div class="card-body">
                                @foreach($allServicios as $servicio)
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="servicio_{{ $servicio->id }}" value="{{ $servicio->id }}" wire:model.defer="serviciosSeleccionados">
                                        <label class="custom-control-label" for="servicio_{{ $servicio->id }}">{{ $servicio->nombre }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                         @error('serviciosSeleccionados') <span class="text-danger small">{{ $message }}</span> @enderror
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
                    <p>¿Está seguro de que desea eliminar este tipo de libreta?</p>
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
