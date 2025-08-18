<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="mb-0">Infracciones de Tránsito</h4>
                </div>
                <div class="col-md-6 text-right">
                    <button wire:click="create()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Infracción
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <input wire:model="search" type="text" class="form-control"
                           placeholder="Buscar por artículo, descripción o decreto...">
                </div>
                <div class="col-md-3">
                    <select wire:model="perPage" class="form-control">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                    </select>
                </div>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>
                                <button class="btn btn-link text-white p-0" wire:click="sortBy('articulo')">
                                    Artículo
                                    @if ($sortField === 'articulo')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>Apartado</th>
                            <th>
                                <button class="btn btn-link text-white p-0" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button class="btn btn-link text-white p-0" wire:click="sortBy('importe_ur')">
                                    Importe (UR)
                                    @if ($sortField === 'importe_ur')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>Estado</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($infracciones as $infraccion)
                            <tr>
                                <td><strong>{{ $infraccion->articulo }}</strong></td>
                                <td>{{ $infraccion->apartado }}</td>
                                <td>
                                    {{ Str::limit($infraccion->descripcion, 80) }}
                                    @if ($infraccion->decreto)
                                        <small class="text-muted d-block">{{ $infraccion->decreto }}</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-info">{{ $infraccion->importe_formateado }}</span></td>
                                <td>
                                    <button wire:click="toggleStatus({{ $infraccion->id }})"
                                            class="btn btn-sm {{ $infraccion->activo ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $infraccion->activo ? 'Activa' : 'Inactiva' }}
                                    </button>
                                </td>
                                <td>
                                    <button wire:click="edit({{ $infraccion->id }})"
                                            class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="confirm('¿Está seguro de eliminar esta infracción?') || event.stopImmediatePropagation()"
                                            wire:click="delete({{ $infraccion->id }})"
                                            class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron infracciones</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if ($infracciones->hasPages())
                <div class="mt-3">
                    {{ $infracciones->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    @if($isOpen)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $isEdit ? 'Editar Infracción' : 'Nueva Infracción' }}
                        </h5>
                        <button type="button" class="close" wire:click="closeModal()">
                            <span>&times;</span>
                        </button>
                    </div>

                    <form wire:submit.prevent="store">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="articulo">Artículo <span class="text-danger">*</span></label>
                                        <input wire:model="articulo" type="text" class="form-control @error('articulo') is-invalid @enderror"
                                               id="articulo" placeholder="Ej: 103">
                                        @error('articulo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apartado">Apartado</label>
                                        <input wire:model="apartado" type="text" class="form-control @error('apartado') is-invalid @enderror"
                                               id="apartado" placeholder="Ej: 2A">
                                        @error('apartado')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                                <textarea wire:model="descripcion" class="form-control @error('descripcion') is-invalid @enderror"
                                          id="descripcion" rows="3" placeholder="Descripción de la infracción"></textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="importe_ur">Importe (UR) <span class="text-danger">*</span></label>
                                        <input wire:model="importe_ur" type="number" step="0.1"
                                               class="form-control @error('importe_ur') is-invalid @enderror"
                                               id="importe_ur" placeholder="0.0">
                                        @error('importe_ur')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="decreto">Decreto</label>
                                        <input wire:model="decreto" type="text" class="form-control @error('decreto') is-invalid @enderror"
                                               id="decreto" placeholder="Ej: Decreto Nº 81/014">
                                        @error('decreto')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input wire:model="activo" type="checkbox" class="form-check-input" id="activo">
                                    <label class="form-check-label" for="activo">
                                        Infracción activa
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                {{ $isEdit ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

<style>
    .modal {
        z-index: 1050;
    }
    .modal-backdrop {
        z-index: 1040;
    }
</style>
</div>
