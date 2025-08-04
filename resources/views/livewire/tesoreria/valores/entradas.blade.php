<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Gestión de entradas de libretas de recibos</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus mr-2"></i>Nueva Entrada
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
                                placeholder="Buscar por comprobante, interno o valor...">
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
                        <label class="font-weight-bold col-form-label-sm">Fecha</label>
                        <select class="form-control form-control-sm" wire:model="filterFecha">
                            <option value="">Todas</option>
                            <option value="hoy">Hoy</option>
                            <option value="semana">Esta Semana</option>
                            <option value="mes">Este Mes</option>
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

    {{-- Tabla de entradas --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th wire:click="sortBy('fecha')" style="cursor: pointer;" class="text-nowrap text-center">
                                Fecha
                                @if ($sortField === 'fecha')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('valores_id')" style="cursor: pointer;" class="text-nowrap text-start">
                                Valor
                                @if ($sortField === 'valores_id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('comprobante')" style="cursor: pointer;" class="text-nowrap text-center">
                                Comprobante
                                @if ($sortField === 'comprobante')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th class="text-nowrap text-center">Rango</th>
                            <th class="text-nowrap text-center">Cantidad</th>
                            <th width="180" class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entradas as $entrada)
                            <tr>
                                <td class="text-center">{{ $entrada->fecha->format('d/m/Y') }}</td>
                                <td>
                                    <strong>{{ $entrada->valor->nombre }}</strong><br>
                                    <small class="text-muted">{{ $entrada->valor->tipo_valor_texto }}</small>
                                </td>
                                <td class="text-center">{{ $entrada->comprobante }}</td>
                                <td class="text-center">{{ number_format($entrada->desde) }} - {{ number_format($entrada->hasta) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-primary text-white">{{ number_format($entrada->total_recibos) }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info"
                                            wire:click="openDetailModal({{ $entrada->id }})" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary"
                                            wire:click="openEditModal({{ $entrada->id }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger"
                                            wire:click="openDeleteModal({{ $entrada->id }})" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No se encontraron entradas registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($entradas->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $entradas->firstItem() }} a {{ $entradas->lastItem() }} de
                            {{ $entradas->total() }} resultados
                        </div>
                        {{ $entradas->links() }}
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
                        {{ $showCreateModal ? 'Registrar Nueva Entrada' : 'Editar Entrada' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Valor <span class="text-danger">*</span></label>
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
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                    wire:model="fecha">
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Comprobante <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('comprobante') is-invalid @enderror"
                                    wire:model="comprobante" placeholder="Ej: Factura 12345">
                                @error('comprobante')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Número Interno (Opcional)</label>
                                <input type="text" class="form-control @error('interno') is-invalid @enderror"
                                    wire:model="interno" placeholder="Ej: Lote A-2023">
                                @error('interno')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Desde <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('desde') is-invalid @enderror"
                                    wire:model="desde" placeholder="1" min="1">
                                @error('desde')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Hasta <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('hasta') is-invalid @enderror"
                                    wire:model="hasta" placeholder="100" min="1">
                                @error('hasta')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-12">
                                <label>Observaciones</label>
                                <textarea class="form-control @error('observaciones') is-invalid @enderror" wire:model="observaciones" rows="3"
                                    placeholder="Observaciones adicionales..."></textarea>
                                @error('observaciones')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    @if ($showCreateModal)
                        <button type="button" class="btn btn-primary" wire:click="create">
                            <i class="fas fa-save mr-2"></i>Registrar Entrada
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="update">
                            <i class="fas fa-save mr-2"></i>Actualizar Entrada
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
                @if ($selectedEntrada)
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Eliminación
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea eliminar la entrada de <strong>{{ $selectedEntrada->total_recibos }}</strong>
                            recibos del valor <strong>{{ $selectedEntrada->valor->nombre }}</strong> con comprobante
                            <strong>{{ $selectedEntrada->comprobante }}</strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Esta acción no se puede deshacer. La eliminación de esta entrada podría afectar el stock.
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

    {{-- Modal Detalles --}}
    <div class="modal fade" id="detailModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                @if ($selectedEntrada)
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle mr-2"></i>Detalles de Entrada
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Valor:</strong> {{ $selectedEntrada->valor->nombre }}</p>
                                <p><strong>Fecha:</strong> {{ $selectedEntrada->fecha->format('d/m/Y') }}</p>
                                <p><strong>Comprobante:</strong> {{ $selectedEntrada->comprobante }}</p>
                                <p><strong>Número Interno:</strong> {{ $selectedEntrada->interno ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Desde:</strong> {{ number_format($selectedEntrada->desde) }}</p>
                                <p><strong>Hasta:</strong> {{ number_format($selectedEntrada->hasta) }}</p>
                                <p><strong>Cantidad:</strong> {{ number_format($selectedEntrada->total_recibos) }}</p>
                            </div>
                            <div class="col-12">
                                <p><strong>Observaciones:</strong> {{ $selectedEntrada->observaciones ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

