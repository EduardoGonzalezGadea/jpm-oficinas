<div wire:key="stock-component">
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Resumen y gestión del stock de libretas de recibos</p>
        </div>
        <button type="button" class="btn btn-outline-success" wire:click="exportarStock">
            <i class="fas fa-file-excel mr-2"></i>Exportar Stock
        </button>
    </div>

    {{-- Estadísticas Generales --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ number_format($estadisticasGenerales['total_valores']) }}</h3>
                    <small>Valores Activos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ number_format($estadisticasGenerales['total_recibos_stock']) }}</h3>
                    <small>Recibos en Stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ number_format($estadisticasGenerales['total_recibos_uso']) }}</h3>
                    <small>Recibos en Uso</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ number_format($estadisticasGenerales['valores_stock_bajo']) }}</h3>
                    <small>Valores con Stock Bajo</small>
                </div>
            </div>
        </div>
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
                        <label class="font-weight-bold col-form-label-sm">Filtrar por Valor</label>
                        <select class="form-control form-control-sm" wire:model="filterValor">
                            <option value="">Todos los Valores</option>
                            @foreach ($valoresParaFiltro as $valorFiltro)
                                <option value="{{ $valorFiltro->id }}">{{ $valorFiltro->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="font-weight-bold col-form-label-sm">Filtrar por Tipo</label>
                        <select class="form-control form-control-sm" wire:model="filterTipo">
                            <option value="">Todos los Tipos</option>
                            <option value="pesos">Pesos</option>
                            <option value="UI">UI</option>
                            <option value="SVE">Sin Valor</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" wire:model="filterStockBajo"
                                id="filterStockBajo">
                            <label class="form-check-label" for="filterStockBajo">
                                Mostrar solo Stock Bajo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="resetFilters">
                        <i class="fas fa-times mr-1"></i>Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de stock --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th wire:click="sortBy('nombre')" style="cursor: pointer;" class="text-nowrap text-start">
                                Valor
                                @if ($sortField === 'nombre')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('stock_total')" style="cursor: pointer;" class="text-nowrap text-center">
                                Total Recibos
                                @if ($sortField === 'stock_total')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('libretas_completas')" style="cursor: pointer;" class="text-nowrap text-center">
                                Libretas Completas
                                @if ($sortField === 'libretas_completas')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </th>
                            <th class="text-nowrap text-center">Recibos en Uso</th>
                            <th class="text-nowrap text-center">Recibos Disponibles</th>
                            <th width="100" class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valores as $valor)
                            <tr>
                                <td>
                                    <strong>{{ $valor->nombre }}</strong><br>
                                    <small class="text-muted">{{ $valor->tipo_valor_texto }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary text-white">{{ number_format($valor->resumen_stock['stock_total']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success text-white">{{ number_format($valor->resumen_stock['libretas_completas']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-warning text-white">{{ number_format($valor->resumen_stock['recibos_en_uso']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info text-white">{{ number_format($valor->resumen_stock['recibos_disponibles']) }}</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-info btn-sm"
                                        wire:click="openDetailModal({{ $valor->id }})" title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No se encontraron valores con stock para mostrar
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Detalles de Stock --}}
    <div class="modal fade" id="detailStockModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                @if ($selectedValor)
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-chart-bar mr-2"></i>Detalle de Stock: {{ $selectedValor->nombre }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fas fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Resumen General del Valor Seleccionado --}}
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($detalleStock['resumen_general']['stock_total']) }}</h3>
                                        <small>Total Recibos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($detalleStock['resumen_general']['libretas_completas']) }}
                                        </h3>
                                        <small>Libretas Completas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($detalleStock['resumen_general']['recibos_en_uso']) }}</h3>
                                        <small>Recibos en Uso</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($detalleStock['resumen_general']['recibos_disponibles']) }}
                                        </h3>
                                        <small>Recibos Disponibles</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Alertas --}}
                        @if (count($detalleStock['alertas']) > 0)
                            <div class="alert alert-danger mb-4">
                                <h6 class="alert-heading"><i class="fas fa-bell mr-2"></i>Alertas de Stock</h6>
                                <ul class="mb-0">
                                    @foreach ($detalleStock['alertas'] as $alerta)
                                        <li><i class="{{ $alerta['icono'] }} mr-2"></i>{{ $alerta['mensaje'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Detalle por Concepto --}}
                        @if (count($detalleStock['conceptos_detalle']) > 0)
                            <h6 class="mb-3">Detalle por Concepto</h6>
                            @foreach ($detalleStock['conceptos_detalle'] as $conceptoDetalle)
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">{{ $conceptoDetalle['concepto']->concepto }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <p class="mb-0"><strong>Asignados:</strong>
                                                    {{ number_format($conceptoDetalle['resumen']['total_asignados']) }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-0"><strong>Disponibles:</strong>
                                                    {{ number_format($conceptoDetalle['resumen']['total_disponibles']) }}</p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-0"><strong>Utilizados:</strong>
                                                    {{ number_format($conceptoDetalle['resumen']['total_utilizados']) }}</p>
                                            </div>
                                        </div>
                                        @if (count($conceptoDetalle["usos"]) > 0)
                                            <h6 class="mt-3">Libretas en Uso:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Rango Original</th>
                                                            <th>Rango Disponible</th>
                                                            <th>Total Recibos</th>
                                                            <th>Disponibles</th>
                                                            <th>Utilizados</th>
                                                            <th>% Uso</th>
                                                            <th>Interno</th>
                                                            <th>Fecha Asignación</th>
                                                            <th>Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($conceptoDetalle['usos'] as $uso)
                                                            <tr>
                                                                <td>{{ $uso['rango_original'] }}</td>
                                                                <td>{{ $uso['rango_disponible'] }}</td>
                                                                <td>{{ number_format($uso['total_recibos']) }}</td>
                                                                <td>
                                                                    <input type="number" class="form-control form-control-sm"
                                                                        wire:model.defer="detalleStock.conceptos_detalle.{{ $loop->parent->index }}.usos.{{ $loop->index }}.recibos_disponibles"
                                                                        wire:change="actualizarRecibosUso({{ $uso['id'] }}, $event.target.value)"
                                                                        min="0" max="{{ $uso['total_recibos'] }}">
                                                                </td>
                                                                <td>{{ number_format($uso['recibos_utilizados']) }}</td>
                                                                <td>
                                                                    <div class="progress" style="height: 20px;">
                                                                        <div class="progress-bar bg-{{ $uso['porcentaje_uso'] > 75 ? 'danger' : ($uso['porcentaje_uso'] > 50 ? 'warning' : 'success') }}"
                                                                            style="width: {{ $uso['porcentaje_uso'] }}%">
                                                                            {{ $uso['porcentaje_uso'] }}%
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>{{ $uso['interno'] ?? 'N/A' }}</td>
                                                                <td>{{ $uso['fecha_asignacion'] }}</td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                                        wire:click="marcarLibretaAgotada({{ $uso['id'] }})"
                                                                        title="Marcar como Agotada" @if ($uso['recibos_disponibles'] == 0) disabled @endif>
                                                                        <i class="fas fa-check-double"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="alert alert-info alert-sm">
                                                No hay libretas en uso para este concepto.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay conceptos activos para este valor.
                            </div>
                        @endif

                        {{-- Movimientos Recientes --}}
                        @if (count($detalleStock["movimientos_recientes"]) > 0)
                            <h6 class="mb-3 mt-4">Movimientos Recientes (Últimos 10)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Fecha</th>
                                            <th>Comprobante</th>
                                            <th>Rango</th>
                                            <th>Cantidad</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($detalleStock['movimientos_recientes'] as $movimiento)
                                            <tr>
                                                <td>
                                                    <span
                                                        class="badge text-white bg-{{ $movimiento['tipo'] === 'entrada' ? 'success' : 'danger' }}">
                                                        {{ ucfirst($movimiento['tipo']) }}
                                                    </span>
                                                </td>
                                                <td>{{ $movimiento['fecha'] }}</td>
                                                <td>{{ $movimiento['comprobante'] }}</td>
                                                <td>{{ $movimiento['rango'] }}</td>
                                                <td>{{ number_format($movimiento['cantidad']) }}</td>
                                                <td>
                                                    @if ($movimiento['tipo'] === 'salida')
                                                        Concepto: {{ $movimiento['concepto'] }}<br>
                                                        Responsable: {{ $movimiento['responsable'] ?? 'N/A' }}
                                                    @endif
                                                    @if ($movimiento['observaciones'])
                                                        <br><small class="text-muted">Obs: {{ Str::limit($movimiento['observaciones'], 50) }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No hay movimientos recientes para este valor.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            window.livewire.on('show-detail-modal', () => {
                $('#detailStockModal').modal('show');
            });

            window.livewire.on('hide-detail-modal', () => {
                $('#detailStockModal').modal('hide');
            });
        });
    </script>
    @endpush
</div>
