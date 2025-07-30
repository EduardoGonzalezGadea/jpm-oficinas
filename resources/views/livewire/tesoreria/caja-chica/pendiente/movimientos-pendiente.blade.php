<div>
    <div class="row">
        <div class="col-md-12">

            {{-- Mensajes de éxito/error --}}
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="form-row mb-1 mt-3">
                <div class="col-md-6">
                    <h4>Movimientos del Pendiente</h4>
                </div>

                <div class="col-md-6 text-right">
                    {{-- Botón para crear nuevo movimiento --}}
                    <button type="button" class="btn btn-info {{ $loading ? 'disabled' : '' }}"
                        wire:click="abrirModalCrear" {{ $loading ? 'disabled' : '' }}>
                        @if ($loading)
                            <span class="spinner-border spinner-border-sm mr-1" role="status"
                                aria-hidden="true"></span>
                        @else
                            <i class="fas fa-plus" aria-hidden="true"></i>
                        @endif
                        Nuevo Movimiento
                    </button>
                </div>
            </div>

            {{-- Tabla de movimientos --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Documentos</th>
                            <th class="text-right">Rendido</th>
                            <th class="text-right">Reintegrado</th>
                            <th class="text-right">Recuperado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movimientos as $movimiento)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($movimiento->fechaMovimientos)->format('d/m/Y') }}</td>
                                <td>
                                    <span title="{{ $movimiento->documentos }}">
                                        {{ $movimiento->documentos ? Str::limit($movimiento->documentos, 30) : 'Sin dato' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    $ {{ number_format($movimiento->rendido, 2, '.', ',') }}
                                </td>
                                <td class="text-right">
                                    $ {{ number_format($movimiento->reintegrado, 2, '.', ',') }}
                                </td>
                                <td class="text-right">
                                    $ {{ number_format($movimiento->recuperado, 2, '.', ',') }}
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-warning"
                                            wire:click="abrirModalEditar({{ $movimiento->idMovimientos }})"
                                            title="Editar movimiento" {{ $loading ? 'disabled' : '' }}>
                                            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            wire:click="confirmarEliminacion({{ $movimiento->idMovimientos }})"
                                            title="Eliminar movimiento" {{ $loading ? 'disabled' : '' }}>
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p class="mb-0">No hay movimientos registrados para este pendiente.</p>
                                        <button type="button" class="btn btn-primary mt-2"
                                            wire:click="abrirModalCrear">
                                            <i class="fas fa-plus"></i> Crear primer movimiento
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Resumen mejorado de totales --}}
            @if ($movimientos->count() > 0)
                @php
                    $balance = $this->getBalancePendiente();
                @endphp
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i> Resumen de totales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="border-right">
                                    <h5 class="text-success font-weight-bold">Total Rendido</h5>
                                    <h4 class="font-weight-bold">$
                                        {{ number_format($balance['total_rendido'], 2, '.', ',') }}</h4>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border-right">
                                    <h5 class="text-info font-weight-bold">Total Reintegrado</h5>
                                    <h4 class="font-weight-bold">$
                                        {{ number_format($balance['total_reintegrado'], 2, '.', ',') }}</h4>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="border-right">
                                    <h5 class="text-warning font-weight-bold">Total Recuperado</h5>
                                    <h4 class="font-weight-bold">$
                                        {{ number_format($balance['total_recuperado'], 2, '.', ',') }}</h4>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5 class="{{ $balance['saldo'] >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                    Saldo Actual
                                </h5>
                                <h4
                                    class="font-weight-bold {{ $balance['saldo'] > 0 ? 'text-warning' : '' }}">
                                    $ {{ number_format($balance['saldo'], 2, '.', ',') }}
                                </h4>
                                @if ($balance['saldo'] < 0)
                                    <small class="text-muted">Erróneo</small>
                                @elseif($balance['saldo'] > 0)
                                    <small class="text-muted">No cerrado</small>
                                @else
                                    <small class="text-muted">Cerrado</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal para crear/editar movimiento --}}
    @if ($showModal)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1"
            role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas {{ $editMode ? 'fa-edit' : 'fa-plus' }}"></i>
                            {{ $editMode ? 'Editar Movimiento' : 'Crear Nuevo Movimiento' }}
                        </h5>
                        <button type="button" class="close" wire:click="cerrarModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form wire:submit.prevent="guardarMovimiento">
                        <div class="modal-body">
                            <input type="hidden" id="movimiento_monto_pendiente" value="{{ $pendiente->montoPendientes }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechaMovimientos">
                                            Fecha <span class="text-danger">*</span>
                                        </label>
                                        <input type="date"
                                            class="form-control @error('fechaMovimientos') is-invalid @enderror"
                                            id="fechaMovimientos" wire:model.lazy="fechaMovimientos"
                                            max="{{ date('Y-m-d') }}">
                                        @error('fechaMovimientos')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="documentos">Documentos</label>
                                        <input type="text"
                                            class="form-control @error('documentos') is-invalid @enderror"
                                            id="documentos" wire:model.lazy="documentos"
                                            placeholder="Ej: Facturas, recibos, etc." maxlength="255">
                                        @error('documentos')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Máximo 255 caracteres
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="movimiento_monto_rendido">
                                            Monto Rendido <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number"
                                                class="form-control @error('rendido') is-invalid @enderror"
                                                id="movimiento_monto_rendido" wire:model.lazy="rendido" step="0.01"
                                                min="0" placeholder="0.00"
                                                oninput="calcularReintegrado()">
                                            @error('rendido')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">
                                            Monto total gastado
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="movimiento_monto_reintegrado">Monto Reintegrado</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number"
                                                class="form-control @error('reintegrado') is-invalid @enderror"
                                                id="movimiento_monto_reintegrado" wire:model.live="reintegrado" step="0.01"
                                                min="0" placeholder="0.00">
                                            @error('reintegrado')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">
                                            Monto devuelto a caja
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="recuperado">Monto Recuperado</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number"
                                                class="form-control @error('recuperado') is-invalid @enderror"
                                                id="recuperado" wire:model.lazy="recuperado" step="0.01"
                                                min="0" placeholder="0.00">
                                            @error('recuperado')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="form-text text-muted">
                                            Monto recuperado por otros medios
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview del balance --}}
                            @if ($rendido > 0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-light border">
                                            <div class="row text-center">
                                                <div class="col-md-3">
                                                    <small class="text-muted font-weight-bold">Rendido</small>
                                                    <div class="font-weight-bold text-success">
                                                        $ {{ number_format($rendido, 2, '.', ',') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted font-weight-bold">Reintegrado</small>
                                                    <div class="font-weight-bold text-info">
                                                        $ {{ number_format($reintegrado ?: 0, 2, '.', ',') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted font-weight-bold">Recuperado</small>
                                                    <div class="font-weight-bold text-warning">
                                                        $ {{ number_format($recuperado ?: 0, 2, '.', ',') }}
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted font-weight-bold">Saldo</small>
                                                    @php
                                                        $balance_preview =
                                                            $pendiente->montoPendientes - ($reintegrado ?: 0) - ($recuperado ?: 0);
                                                    @endphp
                                                    <div
                                                        class="font-weight-bold {{ $balance_preview >= 0 ? 'text-success' : 'text-danger' }}">
                                                        $ {{ number_format($balance_preview, 2, '.', ',') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Información adicional --}}
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Información:</strong>
                                    <ul class="mb-0 mt-1">
                                        <li>El monto rendido es obligatorio y debe ser mayor a 0</li>
                                        <li>Los montos reintegrado y recuperado son opcionales</li>
                                        <li>La suma de reintegrado + recuperado no puede exceder el monto del pendiente</li>
                                        <li>La fecha no puede ser futura</li>
                                    </ul>
                                </small>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModal"
                                {{ $loading ? 'disabled' : '' }}>
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary" {{ $loading ? 'disabled' : '' }}>
                                @if ($loading)
                                    <span class="spinner-border spinner-border-sm mr-1" role="status"
                                        aria-hidden="true"></span>
                                @else
                                    <i class="fas fa-save"></i>
                                @endif
                                {{ $editMode ? 'Actualizar' : 'Guardar' }} Movimiento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal de confirmación para eliminar --}}
    @if ($showDeleteModal)
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1"
            role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Confirmar Eliminación
                        </h5>
                        <button type="button" class="close" wire:click="cancelarEliminacion" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            ¿Está seguro de que desea eliminar este movimiento?
                        </div>
                        <p class="mb-0">Esta acción no se puede deshacer y afectará los totales del pendiente.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelarEliminacion">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="confirmarEliminarMovimiento">
                            <i class="fas fa-trash-alt"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Scripts para mejorar UX --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus en el primer campo cuando se abre el modal
            window.addEventListener('modal-opened', function() {
                setTimeout(function() {
                    const modal = document.querySelector('.modal.show');
                    const firstInput = modal?.querySelector('#fechaMovimientos');
                    if (firstInput) {
                        firstInput.focus();

                        // Scroll al top del modal body si es necesario
                        const modalBody = modal.querySelector('.modal-body');
                        if (modalBody) {
                            modalBody.scrollTop = 0;
                        }
                    }
                }, 150);
            });

            // Toast notifications
            window.addEventListener('show-toast', function(event) {
                const {
                    type,
                    message
                } = event.detail;

                // Crear toast notification
                const toastHtml = `
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
                    <div class="toast-header">
                        <i class="fas fa-${type === 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'} mr-2"></i>
                        <strong class="mr-auto">${type === 'success' ? 'Éxito' : 'Error'}</strong>
                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;

                // Agregar toast al contenedor (crear si no existe)
                let toastContainer = document.querySelector('.toast-container');
                if (!toastContainer) {
                    toastContainer = document.createElement('div');
                    toastContainer.className = 'toast-container';
                    toastContainer.style.cssText =
                    'position: fixed; top: 20px; right: 20px; z-index: 1060;';
                    document.body.appendChild(toastContainer);
                }

                const toastElement = document.createElement('div');
                toastElement.innerHTML = toastHtml;
                toastContainer.appendChild(toastElement.firstElementChild);

                // Mostrar toast
                $('.toast').toast('show');

                // Limpiar toasts después de mostrarlos
                $('.toast').on('hidden.bs.toast', function() {
                    this.remove();
                });
            });

            // Validación en tiempo real para evitar que reintegrado + recuperado > rendido
            function validateBalance() {
                const rendido = parseFloat(document.getElementById('rendido')?.value) || 0;
                const reintegrado = parseFloat(document.getElementById('reintegrado')?.value) || 0;
                const recuperado = parseFloat(document.getElementById('recuperado')?.value) || 0;

                const suma = reintegrado + recuperado;
                const reintegradoInput = document.getElementById('reintegrado');
                const recuperadoInput = document.getElementById('recuperado');

                if (suma > rendido && rendido > 0) {
                    if (reintegradoInput) {
                        reintegradoInput.classList.add('is-invalid');
                    }
                    if (recuperadoInput) {
                        recuperadoInput.classList.add('is-invalid');
                    }

                    // Mostrar mensaje de advertencia
                    let warningDiv = document.querySelector('.balance-warning');
                    if (!warningDiv && rendido > 0) {
                        warningDiv = document.createElement('div');
                        warningDiv.className = 'alert alert-warning balance-warning mt-2';
                        warningDiv.innerHTML =
                            '<i class="fas fa-exclamation-triangle"></i> La suma de reintegrado y recuperado no puede exceder el monto rendido.';
                        document.querySelector('.modal-body').appendChild(warningDiv);
                    }
                } else {
                    if (reintegradoInput) {
                        reintegradoInput.classList.remove('is-invalid');
                    }
                    if (recuperadoInput) {
                        recuperadoInput.classList.remove('is-invalid');
                    }

                    // Remover mensaje de advertencia
                    const warningDiv = document.querySelector('.balance-warning');
                    if (warningDiv) {
                        warningDiv.remove();
                    }
                }
            }

            // Event listeners para validación en tiempo real
            document.addEventListener('input', function(e) {
                if (['rendido', 'reintegrado', 'recuperado'].includes(e.target.id)) {
                    setTimeout(validateBalance, 100);
                }
            });

            // Formatear números mientras se escriben
            document.addEventListener('blur', function(e) {
                if (e.target.type === 'number' && e.target.value) {
                    const value = parseFloat(e.target.value);
                    if (!isNaN(value)) {
                        e.target.value = value.toFixed(2);
                    }
                }
            });

            // Prevenir envío del formulario si hay errores de balance
            document.addEventListener('submit', function(e) {
                if (e.target.closest('.modal')) {
                    const rendido = parseFloat(document.getElementById('rendido')?.value) || 0;
                    const reintegrado = parseFloat(document.getElementById('reintegrado')?.value) || 0;
                    const recuperado = parseFloat(document.getElementById('recuperado')?.value) || 0;

                    if ((reintegrado + recuperado) > rendido) {
                        e.preventDefault();
                        validateBalance();
                    }
                }
            });
        });

        // Funciones utilitarias
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-UY', {
                style: 'currency',
                currency: 'UYU',
                minimumFractionDigits: 2
            }).format(amount);
        }

        // Función para exportar datos (opcional)
        function exportarMovimientos() {
            // Esta función se puede implementar más tarde para exportar a Excel/PDF
            console.log('Función de exportación - Por implementar');
        }
    </script>

    <script>
        function calcularReintegrado() {
            const montoPendiente = parseFloat(document.getElementById('movimiento_monto_pendiente').value) || 0;
            const montoRendido = parseFloat(document.getElementById('movimiento_monto_rendido').value) || 0;
            const montoReintegradoInput = document.getElementById('movimiento_monto_reintegrado');

            let montoReintegrado = montoPendiente - montoRendido;

            if (montoReintegrado < 0) {
                montoReintegrado = 0;
            }

            montoReintegradoInput.value = montoReintegrado.toFixed(2);

            // Disparar el evento input para que Livewire actualice el valor
            const event = new Event('input', { bubbles: true });
            montoReintegradoInput.dispatchEvent(event);
        }
    </script>

    {{-- Estilos adicionales --}}
    <style>
        .toast-container .toast {
            min-width: 300px;
            margin-bottom: 10px;
        }

        .border-right {
            border-right: 1px solid #dee2e6 !important;
        }

        @media (max-width: 768px) {
            .border-right {
                border-right: none !important;
                border-bottom: 1px solid #dee2e6 !important;
                margin-bottom: 15px;
                padding-bottom: 15px;
            }
        }

        .modal-lg {
            max-width: 900px;
        }

        .modal-dialog-scrollable {
            max-height: calc(100vh - 3.5rem);
        }

        .modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 3.5rem);
            overflow: hidden;
        }

        .modal-dialog-scrollable .modal-body {
            overflow-y: auto;
            max-height: calc(100vh - 210px);
            /* Ajustado para header y footer */
        }

        @media (max-width: 576px) {
            .modal-dialog-scrollable {
                max-height: calc(100vh - 1rem);
            }

            .modal-dialog-scrollable .modal-content {
                max-height: calc(100vh - 1rem);
            }

            .modal-dialog-scrollable .modal-body {
                max-height: calc(100vh - 160px);
            }

            .modal-lg {
                max-width: calc(100% - 1rem);
                margin: 0.5rem;
            }
        }

        .table td {
            vertical-align: middle;
        }

        .btn-group .btn {
            margin-right: 0;
        }

        .spinner-border-sm {
            width: 0.875rem;
            height: 0.875rem;
        }

        .alert-light {
            background-color: #f8f9fa;
            border-color: #e9ecef;
        }

        .form-text {
            font-size: 0.775em;
        }

        .balance-warning {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, .075);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 1rem);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-content {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
        }

        /* Scrollbar personalizada para el modal body */
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</div>
