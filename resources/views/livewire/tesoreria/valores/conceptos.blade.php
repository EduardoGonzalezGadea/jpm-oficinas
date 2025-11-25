<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Gestión de conceptos asociados a los valores</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus mr-2"></i>Nuevo Concepto
        </button>
    </div>

    {{-- Filtros y búsqueda --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <div class="row d-flex justify-content-between align-items-center">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold col-form-label-sm">Buscar</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="search"
                                placeholder="Buscar por concepto o descripción...">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-bold col-form-label-sm">Valor Asociado</label>
                        <select class="form-control form-control-sm" wire:model="filterValor">
                            <option value="">Todos</option>
                            @foreach ($valores as $valor)
                                <option value="{{ $valor->id }}">{{ $valor->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="font-weight-bold col-form-label-sm">Estado</label>
                        <select class="form-control form-control-sm" wire:model="filterActivo">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="font-weight-bold col-form-label-sm">Por página</label>
                        <select class="form-control form-control-sm" wire:model="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('search', '')">
                        <i class="fas fa-times mr-1"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de conceptos --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th wire:click="sortBy('concepto')" style="cursor: pointer;" class="text-nowrap text-start">
                                Concepto
                                @if ($sortField === 'concepto')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('valores_id')" style="cursor: pointer;" class="text-nowrap text-center">
                                Valor Asociado
                                @if ($sortField === 'valores_id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('monto')" style="cursor: pointer;" class="text-nowrap text-center">
                                Monto
                                @if ($sortField === 'monto')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th class="text-nowrap text-center">Estado</th>
                            <th width="180" class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conceptos as $concepto)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $concepto->concepto }}</strong>
                                        @if ($concepto->descripcion)
                                            <br><small
                                                class="text-muted">{{ Str::limit($concepto->descripcion, 50) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info text-white">{{ $concepto->valor->nombre }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($concepto->tipo_monto === 'pesos')
                                        ${{ number_format($concepto->monto, 2) }}
                                    @elseif ($concepto->tipo_monto === 'UR')
                                        {{ number_format($concepto->monto, 2) }} UR
                                    @else
                                        {{ number_format($concepto->monto, 2) }}%
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($concepto->activo)
                                        <span class="badge badge-success text-white">Activo</span>
                                    @else
                                        <span class="badge badge-danger text-white">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary"
                                            wire:click="openEditModal({{ $concepto->id }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-outline-{{ $concepto->activo ? 'warning' : 'success' }}"
                                            wire:click="toggleActive({{ $concepto->id }})"
                                            title="{{ $concepto->activo ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas fa-{{ $concepto->activo ? 'pause' : 'play' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger"
                                            wire:click="openDeleteModal({{ $concepto->id }})" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No se encontraron conceptos registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($conceptos->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $conceptos->firstItem() }} a {{ $conceptos->lastItem() }} de
                            {{ $conceptos->total() }} resultados
                        </div>
                        {{ $conceptos->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    <div class="modal fade" id="createEditModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $showCreateModal ? 'Crear Nuevo Concepto' : 'Editar Concepto' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Valor Asociado <span class="text-danger">*</span></label>
                                <select class="form-control @error('valores_id') is-invalid @enderror"
                                    wire:model="valores_id">
                                    <option value="">Seleccione un valor</option>
                                    @foreach ($valores as $valor)
                                        <option value="{{ $valor->id }}">{{ $valor->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('valores_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Concepto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('concepto') is-invalid @enderror"
                                    wire:model="concepto" placeholder="Ej: Cuota Social">
                                @error('concepto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Monto <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('monto') is-invalid @enderror"
                                    wire:model="monto" placeholder="0.00" min="0">
                                @error('monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tipo de Monto <span class="text-danger">*</span></label>
                                <select class="form-control @error('tipo_monto') is-invalid @enderror"
                                    wire:model="tipo_monto">
                                    <option value="pesos">Pesos</option>
                                    <option value="UI">Unidad Indexada</option>
                                    <option value="porcentaje">Porcentaje</option>
                                </select>
                                @error('tipo_monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-12">
                                <label>Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" wire:model="descripcion" rows="3"
                                    placeholder="Descripción opcional del concepto..."></textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="activo"
                                        id="activoConcepto">
                                    <label class="form-check-label" for="activoConcepto">
                                        Activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    @if ($showCreateModal)
                        <button type="button" class="btn btn-primary" wire:click="create">
                            <i class="fas fa-save mr-2"></i>Crear Concepto
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="update">
                            <i class="fas fa-save mr-2"></i>Actualizar Concepto
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Eliminar --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                @if ($selectedConcepto)
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Eliminación
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea eliminar el concepto <strong>{{ $selectedConcepto->concepto }}</strong>
                            asociado al valor <strong>{{ $selectedConcepto->valor->nombre }}</strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Esta acción no se puede deshacer. Solo se pueden eliminar conceptos sin movimientos asociados.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" wire:click="delete">
                            <i class="fas fa-trash mr-2"></i>Eliminar
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
