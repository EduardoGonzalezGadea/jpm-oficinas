<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Resumen y gestión del stock de libretas de recibos</p>
        </div>
        <button type="button" class="btn btn-outline-success" wire:click="exportarStock">
            <i class="fas fa-file-excel me-2"></i>Exportar Stock
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
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <div class="row d-flex justify-content-between align-items-center">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label font-weight-bold col-form-label-sm">Filtrar por Valor</label>
                        <select class="form-select form-control-sm" wire:model="filterValor">
                            <option value="">Todos los Valores</option>
                            @foreach ($valoresParaFiltro as $valorFiltro)
                                <option value="{{ $valorFiltro->id }}">{{ $valorFiltro->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label font-weight-bold col-form-label-sm">Filtrar por Tipo</label>
                        <select class="form-select form-control-sm" wire:model="filterTipo">
                            <option value="">Todos los Tipos</option>
                            <option value="pesos">Pesos</option>
                            <option value="UR">UR</option>
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
                        <i class="fas fa-times me-1"></i>Limpiar Filtros
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
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('stock_total')" style="cursor: pointer;" class="text-nowrap text-center">
                                Total Recibos
                                @if ($sortField === 'stock_total')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('libretas_completas')" style="cursor: pointer;" class="text-nowrap text-center">
                                Libretas Completas
                                @if ($sortField === 'libretas_completas')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
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
                                    <span class="badge bg-primary text-white">{{ number_format($valor->resumen_stock['stock_total']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success text-white">{{ number_format($valor->resumen_stock['libretas_completas']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-white">{{ number_format($valor->resumen_stock['recibos_en_uso']) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-white">{{ number_format($valor->resumen_stock['recibos_disponibles']) }}</span>
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
                            <i class="fas fa-chart-bar me-2"></i>Detalle de Stock: {{ $selectedValor->nombre }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
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
                                <h6 class="alert-heading"><i class="fas fa-bell me-2"></i>Alertas de Stock</h6>
                                <ul class="mb-0">
                                    @foreach ($detalleStock['alertas'] as $alerta)
                                        <li><i class="{{ $alerta['icono'] }} me-2"></i>{{ $alerta['mensaje'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Detalle por Concepto --}}
                        @if ($detalleStock['conceptos_detalle']->count() > 0)
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
                                        @if ($conceptoDetalle['usos']->count() > 0)
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
                                <i class="fas fa-info-circle me-2"></i>
                                No hay conceptos activos para este valor.
                            </div>
                        @endif

                        {{-- Movimientos Recientes --}}
                        @if ($detalleStock['movimientos_recientes']->count() > 0)
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
                                                        class="badge bg-{{ $movimiento['tipo'] === 'entrada' ? 'success' : 'danger' }}">
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
                                <i class="fas fa-info-circle me-2"></i>
                                No hay movimientos recientes para este valor.
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Script para manejar modales con Vanilla JS --}}
    <script>
        // Función para abrir modal manualmente
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = document.createElement('div');

            if (!modal) {
                console.error('Modal no encontrado:', modalId);
                return;
            }

            // Crear backdrop
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.zIndex = '1040';
            document.body.appendChild(backdrop);

            // Mostrar modal
            modal.style.display = 'block';
            modal.style.zIndex = '1050';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');

            // Agregar clase al body
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '17px'; // Simular scrollbar

            // Enfocar el modal
            modal.focus();

            // Configurar cierre con ESC
            const closeOnEsc = function(e) {
                if (e.key === 'Escape') {
                    hideModal(modalId);
                    document.removeEventListener('keydown', closeOnEsc);
                }
            };
            document.addEventListener('keydown', closeOnEsc);

            // Configurar cierre al hacer clic en backdrop
            backdrop.addEventListener('click', function() {
                hideModal(modalId);
            });

            // Configurar botones de cierre
            const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    hideModal(modalId);
                });
            });
        }

        // Función para cerrar modal manualmente
        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = document.querySelector('.modal-backdrop');

            if (!modal) return;

            // Ocultar modal
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');

            // Remover backdrop
            if (backdrop) {
                backdrop.remove();
            }

            // Remover clases del body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }

        // Event listeners para eventos de Livewire
        window.addEventListener('show-create-edit-modal', function(event) {
            console.log('Evento show-create-edit-modal recibido!', event);
            showModal('createEditModal');
        });

        window.addEventListener('hide-create-edit-modal', function(event) {
            console.log('Evento hide-create-edit-modal recibido!', event);
            hideModal('createEditModal');
        });

        window.addEventListener('show-delete-modal', function(event) {
            console.log('Evento show-delete-modal recibido!', event);
            showModal('deleteModal');
        });

        window.addEventListener('hide-delete-modal', function(event) {
            console.log('Evento hide-delete-modal recibido!', event);
            hideModal('deleteModal');
        });

        window.addEventListener('show-detail-modal', function(event) {
            console.log('Evento show-detail-modal recibido!', event);
            showModal('detailStockModal');
        });

        window.addEventListener('hide-detail-modal', function(event) {
            console.log('Evento hide-detail-modal recibido!', event);
            hideModal('detailStockModal');
        });

        // Configurar cuando Livewire esté listo
        document.addEventListener('livewire:load', function() {
            // Listener para el modal de detalle de stock
            const detailModal = document.getElementById('detailStockModal');
            if (detailModal) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            const modal = mutation.target;
                            if (modal.style.display === 'none' && !modal.classList.contains('show')) {
                                // Opcional: Realizar alguna acción cuando el modal de detalle se cierra
                                // Por ejemplo, resetear alguna propiedad específica si fuera necesario.
                                // Livewire.find('{{ $_instance->id }}')?.set('selectedValor', null);
                            }
                        }
                    });
                });

                observer.observe(detailModal, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }
        });
    </script>

    {{-- Agrega este CSS en tu vista o layout --}}
    <style>
        /* Estilos para modales sin Bootstrap JS */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            outline: 0;
        }

        .modal.show {
            display: block !important;
        }

        .modal-dialog {
            position: relative;
            width: auto;
            margin: 0.5rem;
            pointer-events: none;
            transform: translate(0, 0);
            transition: transform 0.3s ease-out;
        }

        .modal.show .modal-dialog {
            transform: translate(0, 0);
        }

        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 0.3rem;
            outline: 0;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background-color: #000;
            opacity: 0.5;
        }

        .modal-backdrop.show {
            opacity: 0.5;
        }

        body.modal-open {
            overflow: hidden;
        }

        /* Centrar modales */
        @media (min-width: 576px) {
            .modal-dialog {
                max-width: 500px;
                margin: 1.75rem auto;
            }

            .modal-dialog.modal-lg {
                max-width: 800px;
            }

            .modal-dialog.modal-xl {
                max-width: 1140px;
            }
        }

        /* Animaciones suaves */
        .modal {
            transition: opacity 0.15s linear;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: translate(0, -50px);
        }

        .modal.show .modal-dialog {
            transform: none;
        }
    </style>
</div>
