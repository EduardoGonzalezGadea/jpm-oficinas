<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Gestión de libretas de recibos y su stock disponible</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus me-2"></i>Nuevo Valor
        </button>
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
                        <label class="form-label font-weight-bold col-form-label-sm">Buscar</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model="search"
                                placeholder="Buscar por nombre o descripción...">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label font-weight-bold col-form-label-sm">Tipo de Valor</label>
                        <select class="form-select form-control-sm" wire:model="filterTipo">
                            <option value="">Todos</option>
                            <option value="pesos">Pesos</option>
                            <option value="UR">UR</option>
                            <option value="SVE">Sin Valor</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label font-weight-bold col-form-label-sm">Estado</label>
                        <select class="form-select form-control-sm" wire:model="filterActivo">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label font-weight-bold col-form-label-sm">Por página</label>
                        <select class="form-select form-control-sm" wire:model="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('search', '')">
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
                            <th wire:click="sortBy('nombre')" style="cursor: pointer;" class="text-nowrap text-start">
                                Nombre
                                @if ($sortField === 'nombre')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('recibos')" style="cursor: pointer;" class="text-nowrap text-center">
                                Recibos/Libreta
                                @if ($sortField === 'recibos')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('tipo_valor')" style="cursor: pointer;"
                                class="text-nowrap text-center">
                                Tipo
                                @if ($sortField === 'tipo_valor')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th class="text-nowrap text-center">Valor</th>
                            <th class="text-nowrap text-center">Stock Disponible</th>
                            <th class="text-nowrap text-center">Estado</th>
                            <th width="180" class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valores as $valor)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $valor->nombre }}</strong>
                                        @if ($valor->descripcion)
                                            <br><small
                                                class="text-muted">{{ Str::limit($valor->descripcion, 50) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ number_format($valor->recibos) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary text-white">{{ $valor->tipo_valor_texto }}</span>
                                </td>
                                <td>
                                    @if ($valor->valor)
                                        ${{ number_format($valor->valor, 2) }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <small><strong>{{ number_format($valor->resumen_stock['stock_total']) }}</strong>
                                            recibos</small>
                                        <small
                                            class="text-muted">{{ number_format($valor->resumen_stock['libretas_completas']) }}
                                            libretas completas</small>
                                        @if ($valor->resumen_stock['recibos_en_uso'] > 0)
                                            <small
                                                class="text-warning">{{ number_format($valor->resumen_stock['recibos_en_uso']) }}
                                                en uso</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($valor->activo)
                                        <span class="badge bg-success text-white">Activo</span>
                                    @else
                                        <span class="badge bg-danger text-white">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info"
                                            wire:click="openStockModal({{ $valor->id }})" title="Ver Stock">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary"
                                            wire:click="openEditModal({{ $valor->id }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-outline-{{ $valor->activo ? 'warning' : 'success' }}"
                                            wire:click="toggleActive({{ $valor->id }})"
                                            title="{{ $valor->activo ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas fa-{{ $valor->activo ? 'pause' : 'play' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger"
                                            wire:click="openDeleteModal({{ $valor->id }})" title="Eliminar">
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

            @if ($valores->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $valores->firstItem() }} a {{ $valores->lastItem() }} de
                            {{ $valores->total() }} resultados
                        </div>
                        {{ $valores->links() }}
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
                        {{ $showCreateModal ? 'Crear Nuevo Valor' : 'Editar Valor' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                    wire:model="nombre" placeholder="Ej: Recibos de Agua">
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Recibos por Libreta <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('recibos') is-invalid @enderror"
                                    wire:model="recibos" placeholder="100" min="1">
                                @error('recibos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Valor <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_valor') is-invalid @enderror"
                                    wire:model="tipo_valor">
                                    <option value="pesos">Pesos</option>
                                    <option value="UR">Unidad Reajustable</option>
                                    <option value="SVE">Sin Valor Escrito</option>
                                </select>
                                @error('tipo_valor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor
                                    @if ($tipo_valor !== 'SVE')
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01"
                                        class="form-control @error('valor') is-invalid @enderror" wire:model="valor"
                                        placeholder="0.00" @if ($tipo_valor === 'SVE') disabled @endif>
                                </div>
                                @error('valor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if ($tipo_valor === 'SVE')
                                    <small class="text-muted">El valor no aplica para "Sin Valor Escrito"</small>
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" wire:model="descripcion" rows="3"
                                    placeholder="Descripción opcional del valor..."></textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="activo"
                                        id="activo">
                                    <label class="form-check-label" for="activo">
                                        Activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    @if ($showCreateModal)
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
    <div class="modal fade" id="stockModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                @if ($selectedValor)
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-chart-bar me-2"></i>Resumen de Stock - {{ $selectedValor->nombre }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Resumen General --}}
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        @if ($selectedValor && isset($selectedValor->resumen_stock))
                                            <h3 class="mb-1">
                                                {{ number_format($selectedValor->resumen_stock['stock_total']) }}</h3>
                                        @else
                                            <h3 class="mb-1">0</h3>
                                        @endif
                                        <small>Total Recibos</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($selectedValor->resumen_stock['libretas_completas'] ?? 0) }}
                                        </h3>
                                        <small>Libretas Completas</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($selectedValor->resumen_stock['recibos_en_uso'] ?? 0) }}
                                        </h3>
                                        <small>Recibos en Uso</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h3 class="mb-1">
                                            {{ number_format($selectedValor->resumen_stock['recibos_disponibles'] ?? 0) }}
                                        </h3>
                                        <small>Recibos Disponibles</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Detalle por Concepto --}}
                        @if ($selectedValor->conceptosActivos->count() > 0)
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
                                        @foreach ($selectedValor->conceptosActivos as $concepto)
                                            @php
                                                $resumenConcepto = $concepto->getResumenUso();
                                                $porcentaje =
                                                    $resumenConcepto['total_asignados'] > 0
                                                        ? round(
                                                            ($resumenConcepto['total_utilizados'] /
                                                                $resumenConcepto['total_asignados']) *
                                                                100,
                                                            1,
                                                        )
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Eliminar --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                @if ($selectedValor)
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea eliminar el valor <strong>{{ $selectedValor->nombre }}</strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Esta acción no se puede deshacer. Solo se pueden eliminar valores sin movimientos asociados.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" wire:click="delete">
                            <i class="fas fa-trash me-2"></i>Eliminar
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reemplaza todo el script anterior con este --}}
    <script>
        console.log('=== MODAL SCRIPT VANILLA JS ===');

        // Función para abrir modal manualmente
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = document.createElement('div');

            if (!modal) {
                console.error('Modal no encontrado:', modalId);
                return;
            }

            console.log('Abriendo modal:', modalId);

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

            console.log('Cerrando modal:', modalId);

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
            console.log('✅ Evento show-create-edit-modal recibido');
            showModal('createEditModal');
        });

        window.addEventListener('hide-create-edit-modal', function(event) {
            console.log('✅ Evento hide-create-edit-modal recibido');
            hideModal('createEditModal');
        });

        window.addEventListener('show-stock-modal', function(event) {
            console.log('✅ Evento show-stock-modal recibido');
            showModal('stockModal');
        });

        window.addEventListener('hide-stock-modal', function(event) {
            console.log('✅ Evento hide-stock-modal recibido');
            hideModal('stockModal');
        });

        window.addEventListener('show-delete-modal', function(event) {
            console.log('✅ Evento show-delete-modal recibido');
            showModal('deleteModal');
        });

        window.addEventListener('hide-delete-modal', function(event) {
            console.log('✅ Evento hide-delete-modal recibido');
            hideModal('deleteModal');
        });

        // Función de test
        window.testModal = function() {
            console.log('TEST: Disparando evento show-create-edit-modal...');
            window.dispatchEvent(new CustomEvent('show-create-edit-modal'));
        };

        // Configurar cuando Livewire esté listo
        document.addEventListener('livewire:load', function() {
            console.log('Livewire cargado, configurando eventos adicionales...');

            // Escuchar eventos emit de Livewire
            Livewire.on('log', message => {
                console.log('📝 Log desde Livewire:', message);
            });

            // Limpiar formulario cuando se cierra el modal (simulando hidden.bs.modal)
            const createEditModal = document.getElementById('createEditModal');
            if (createEditModal) {
                // Observador para detectar cuando el modal se cierra
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            const modal = mutation.target;
                            if (modal.style.display === 'none' && !modal.classList.contains(
                                    'show')) {
                                // Modal se cerró, llamar resetForm
                                console.log('Modal cerrado, llamando resetForm...');
                                Livewire.find('{{ $_instance->id }}')?.call('resetForm');
                            }
                        }
                    });
                });

                observer.observe(createEditModal, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }
        });

        console.log('=== SCRIPT VANILLA JS CARGADO ===');
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
