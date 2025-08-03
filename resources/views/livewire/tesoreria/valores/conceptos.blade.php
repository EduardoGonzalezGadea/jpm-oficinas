<div>
    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted mb-0">Gestión de conceptos asociados a los valores</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="openCreateModal">
            <i class="fas fa-plus me-2"></i>Nuevo Concepto
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
                                placeholder="Buscar por concepto o descripción...">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label font-weight-bold col-form-label-sm">Valor Asociado</label>
                        <select class="form-select form-control-sm" wire:model="filterValor">
                            <option value="">Todos</option>
                            @foreach ($valores as $valor)
                                <option value="{{ $valor->id }}">{{ $valor->nombre }}</option>
                            @endforeach
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
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="$set('search', '')">
                        <i class="fas fa-times me-1"></i>Limpiar
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
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('valores_id')" style="cursor: pointer;" class="text-nowrap text-center">
                                Valor Asociado
                                @if ($sortField === 'valores_id')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('monto')" style="cursor: pointer;" class="text-nowrap text-center">
                                Monto
                                @if ($sortField === 'monto')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
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
                                <td>
                                    <span class="badge bg-info text-white">{{ $concepto->valor->nombre }}</span>
                                </td>
                                <td>
                                    @if ($concepto->tipo_monto === 'pesos')
                                        ${{ number_format($concepto->monto, 2) }}
                                    @elseif ($concepto->tipo_monto === 'UR')
                                        {{ number_format($concepto->monto, 2) }} UR
                                    @else
                                        {{ number_format($concepto->monto, 2) }}%
                                    @endif
                                </td>
                                <td>
                                    @if ($concepto->activo)
                                        <span class="badge bg-success text-white">Activo</span>
                                    @else
                                        <span class="badge bg-danger text-white">Inactivo</span>
                                    @endif
                                </td>
                                <td>
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
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Valor Asociado <span class="text-danger">*</span></label>
                                <select class="form-select @error('valores_id') is-invalid @enderror"
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
                            <div class="col-md-6">
                                <label class="form-label">Concepto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('concepto') is-invalid @enderror"
                                    wire:model="concepto" placeholder="Ej: Cuota Social">
                                @error('concepto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control @error('monto') is-invalid @enderror"
                                    wire:model="monto" placeholder="0.00" min="0">
                                @error('monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Monto <span class="text-danger">*</span></label>
                                <select class="form-select @error('tipo_monto') is-invalid @enderror"
                                    wire:model="tipo_monto">
                                    <option value="pesos">Pesos</option>
                                    <option value="UR">Unidad Reajustable</option>
                                    <option value="porcentaje">Porcentaje</option>
                                </select>
                                @error('tipo_monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" wire:model="descripcion" rows="3"
                                    placeholder="Descripción opcional del concepto..."></textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    @if ($showCreateModal)
                        <button type="button" class="btn btn-primary" wire:click="create">
                            <i class="fas fa-save me-2"></i>Crear Concepto
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="update">
                            <i class="fas fa-save me-2"></i>Actualizar Concepto
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
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                        </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro que desea eliminar el concepto <strong>{{ $selectedConcepto->concepto }}</strong>
                            asociado al valor <strong>{{ $selectedConcepto->valor->nombre }}</strong>?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Esta acción no se puede deshacer. Solo se pueden eliminar conceptos sin movimientos asociados.
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

        // Configurar cuando Livewire esté listo
        document.addEventListener('livewire:load', function() {
            // Limpiar formulario cuando se cierra el modal (simulando hidden.bs.modal)
            const createEditModal = document.getElementById('createEditModal');
            if (createEditModal) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            const modal = mutation.target;
                            if (modal.style.display === 'none' && !modal.classList.contains(
                                    'show')) {
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

        .close {
            float: right;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            text-shadow: 0 1px 0 #fff;
            opacity: .5;
        }

        .close:hover {
            color: #000;
            text-decoration: none;
        }

        button.close {
            padding: 0;
            background-color: transparent;
            border: 0;
        }
    </style>
</div>
