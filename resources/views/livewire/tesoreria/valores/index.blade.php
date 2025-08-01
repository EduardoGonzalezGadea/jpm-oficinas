<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Control de Stock de Valores</h4>
            <p class="text-muted mb-0">Gestión de libretas de recibos y su stock disponible</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus me-2"></i>Nuevo Valor
        </button>
    </div>

    {{-- Filtros y búsqueda --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" wire:model="search" placeholder="Buscar por nombre o descripción...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de Valor</label>
                    <select class="form-select" wire:model="filterTipo">
                        <option value="">Todos</option>
                        <option value="pesos">Pesos</option>
                        <option value="UR">UR</option>
                        <option value="SVE">Sin Valor</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select class="form-select" wire:model="filterActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Por página</label>
                    <select class="form-select" wire:model="perPage">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="$set('search', '')">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de valores --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th wire:click="sortBy('nombre')" style="cursor: pointer;">
                                Nombre
                                @if($sortField === 'nombre')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('recibos')" style="cursor: pointer;">
                                Recibos/Libreta
                                @if($sortField === 'recibos')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('tipo_valor')" style="cursor: pointer;">
                                Tipo
                                @if($sortField === 'tipo_valor')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th>Valor</th>
                            <th>Stock Disponible</th>
                            <th>Estado</th>
                            <th width="180">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valores as $valor)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $valor->nombre }}</strong>
                                        @if($valor->descripcion)
                                            <br><small class="text-muted">{{ Str::limit($valor->descripcion, 50) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ number_format($valor->recibos) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $valor->tipo_valor_texto }}</span>
                                </td>
                                <td>
                                    @if($valor->valor)
                                        ${{ number_format($valor->valor, 2) }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $stock = $valor->getStockDisponible();
                                        $libretas = $valor->getLibretasCompletas();
                                        $enUso = $valor->getRecibosEnUso();
                                    @endphp
                                    <div class="d-flex flex-column">
                                        <small><strong>{{ number_format($stock) }}</strong> recibos</small>
                                        <small class="text-muted">{{ number_format($libretas) }} libretas completas</small>
                                        @if($enUso > 0)
                                            <small class="text-warning">{{ number_format($enUso) }} en uso</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($valor->activo)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info" wire:click="openStockModal({{ $valor->id }})" title="Ver Stock">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary" wire:click="openEditModal({{ $valor->id }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-{{ $valor->activo ? 'warning' : 'success' }}" 
                                                wire:click="toggleActive({{ $valor->id }})" 
                                                title="{{ $valor->activo ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas fa-{{ $valor->activo ? 'pause' : 'play' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" wire:click="openDeleteModal({{ $valor->id }})" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No se encontraron valores registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($valores->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $valores->firstItem() }} a {{ $valores->lastItem() }} de {{ $valores->total() }} resultados
                        </div>
                        {{ $valores->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    <div class="modal fade" id="createEditModal" tabindex="-1" wire:ignore.self
         @if($showCreateModal || $showEditModal) style="display: block;" @endif>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $showCreateModal ? 'Crear Nuevo Valor' : 'Editar Valor' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showCreateModal', false); $set('showEditModal', false)"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                       wire:model="nombre" placeholder="Ej: Recibos de Agua">
                                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Recibos por Libreta <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('recibos') is-invalid @enderror" 
                                       wire:model="recibos" placeholder="100" min="1">
                                @error('recibos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Valor <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_valor') is-invalid @enderror" wire:model="tipo_valor">
                                    <option value="pesos">Pesos</option>
                                    <option value="UR">Unidad Reajustable</option>
                                    <option value="SVE">Sin Valor Escrito</option>
                                </select>
                                @error('tipo_valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor 
                                    @if($tipo_valor !== 'SVE') <span class="text-danger">*</span> @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control @error('valor') is-invalid @enderror" 
                                           wire:model="valor" placeholder="0.00" 
                                           @if($tipo_valor === 'SVE') disabled @endif>
                                </div>
                                @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if($tipo_valor === 'SVE')
                                    <small class="text-muted">El valor no aplica para "Sin Valor Escrito"</small>
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                          wire:model="descripcion" rows="3" 
                                          placeholder="Descripción opcional del valor..."></textarea>
                                @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
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
                    <button type="button" class="btn btn-secondary" wire:click="$set('showCreateModal', false); $set('showEditModal', false)">
                        Cancelar
                    </button>
                    @if($showCreateModal)
                        <button type="button" class="btn btn-primary" wire:click="create">
                            <i class="fas fa-save me-2"></i>Crear Valor
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="update">
                            <i class="fas fa-save me-2"></i>Actualizar Valor
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Stock --}}
    @if($showStockModal && $selectedValor)
        <div class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-chart-bar me-2"></i>Resumen de Stock - {{ $selectedValor->nombre }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showStockModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Resumen General --}}
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">{{ number_format($stockResumen['stock_total']) }}</h3>
                                        <small>Total Recibos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">{{ number_format($stockResumen['libretas_completas']) }}</h3>
                                        <small>Libretas Completas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">{{ number_format($stockResumen['recibos_en_uso']) }}</h3>
                                        <small>Recibos en Uso</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">{{ number_format($stockResumen['recibos_disponibles']) }}</h3>
                                        <small>Recibos Disponibles</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Detalle por Concepto --}}
                        @if($selectedValor->conceptosActivos->count() > 0)
                            <h6 class="mb-3">Detalle por Concepto</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Concepto</th>
                                            <th>Asignados</th>
                                            <th>Disponibles</th>
                                            <th>Utilizados</th>
                                            <th>% Uso</th>
                                            <th>Libretas en Uso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedValor->conceptosActivos as $concepto)
                                            @php
                                                $resumenConcepto = $concepto->getResumenUso();
                                                $porcentaje = $resumenConcepto['total_asignados'] > 0 
                                                    ? round(($resumenConcepto['total_utilizados'] / $resumenConcepto['total_asignados']) * 100, 1) 
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td>{{ $concepto->concepto }}</td>
                                                <td>{{ number_format($resumenConcepto['total_asignados']) }}</td>
                                                <td>{{ number_format($resumenConcepto['total_disponibles']) }}</td>
                                                <td>{{ number_format($resumenConcepto['total_utilizados']) }}</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-{{ $porcentaje > 75 ? 'danger' : ($porcentaje > 50 ? 'warning' : 'success') }}" 
                                                             style="width: {{ $porcentaje }}%">
                                                            {{ $porcentaje }}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $resumenConcepto['libretas_en_uso'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay conceptos activos para este valor.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showStockModal', false)">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Eliminar --}}
    @if($showDeleteModal && $selectedValor)
        <div class="modal fade show d-block" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showDeleteModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea eliminar el valor <strong>{{ $selectedValor->nombre }}</strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Esta acción no se puede deshacer. Solo se pueden eliminar valores sin movimientos asociados.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="delete">
                            <i class="fas fa-trash me-2"></i>Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Overlay para modales --}}
    @if($showCreateModal || $showEditModal || $showDeleteModal || $showStockModal)
        <div class="modal-backdrop fade show"></div>
    @endif
</div>