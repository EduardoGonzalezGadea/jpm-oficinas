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
                    <h4 class="mb-0">Listado de Multas de Tránsito</h4>
                </div>
                <div class="col-md-6 text-right">
                    <button wire:click="create()" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Multa
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input wire:model.debounce.500ms="search" type="text" class="form-control"
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

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('articulo')">
                                    Art.
                                    @if ($sortField === 'articulo')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('apartado')">
                                    Apartado
                                    @if ($sortField === 'apartado')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                                                        <!-- --- REFACTORIZADO --- -->
                            <th>
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_original')">
                                    Original
                                    @if ($sortField === 'importe_original')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_unificado')">
                                    Unificado
                                    @if ($sortField === 'importe_unificado')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <!-- --------------------- -->
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($multas as $multa)
                            <tr>
                                                                <td><strong>{{ $multa->articulo }}</strong></td>
                                <td>{{ $multa->apartado }}</td>
                                <td>
                                    {{ $multa->descripcion }}
                                    @if ($multa->decreto)
                                        <small class="text-muted d-block">{{ $multa->decreto }}</small>
                                    @endif
                                </td>
                                <!-- --- REFACTORIZADO --- -->
                                <td class="text-right align-middle">{!! $multa->importe_original_formateado !!}</td>
                                <td class="text-right align-middle">{!! $multa->importe_unificado_formateado !!}</td>
                                <!-- --------------------- -->
                                
                                <td>
                                    <button wire:click="edit({{ $multa->id }})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirm('¿Está seguro de eliminar esta multa?') || event.stopImmediatePropagation()"
                                            wire:click="delete({{ $multa->id }})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron multas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($multas->hasPages())
                <div class="mt-3">{{ $multas->links() }}</div>
            @endif
        </div>
    </div>

    @if($isOpen)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $isEdit ? 'Editar Multa' : 'Nueva Multa' }}</h5>
                        <button type="button" class="close" wire:click="closeModal()"><span>&times;</span></button>
                    </div>

                    <form wire:submit.prevent="store">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="articulo">Artículo <span class="text-danger">*</span></label>
                                    <input wire:model.defer="articulo" type="text" class="form-control @error('articulo') is-invalid @enderror" id="articulo" placeholder="Ej: 103">
                                    @error('articulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="apartado">Apartado</label>
                                    <input wire:model.defer="apartado" type="text" class="form-control @error('apartado') is-invalid @enderror" id="apartado" placeholder="Ej: 2A">
                                    @error('apartado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                                <textarea wire:model.defer="descripcion" class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" rows="3" placeholder="Descripción de la multa"></textarea>
                                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <!-- --- REFACTORIZADO --- -->
                                <div class="col-md-6 form-group">
                                    <label for="importe_original">Importe Original (UR) <span class="text-danger">*</span></label>
                                    <input wire:model.defer="importe_original" type="number" step="0.01" class="form-control @error('importe_original') is-invalid @enderror" id="importe_original" placeholder="0.00">
                                    @error('importe_original')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="importe_unificado">Importe Unificado (UR)</label>
                                    <input wire:model.defer="importe_unificado" type="number" step="0.01" class="form-control @error('importe_unificado') is-invalid @enderror" id="importe_unificado" placeholder="0.00 (opcional)">
                                    @error('importe_unificado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <!-- --------------------- -->
                            </div>

                            <div class="form-group">
                                <label for="decreto">Decreto</label>
                                <input wire:model.defer="decreto" type="text" class="form-control @error('decreto') is-invalid @enderror" id="decreto" placeholder="Ej: Decreto Nº 81/014">
                                @error('decreto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
