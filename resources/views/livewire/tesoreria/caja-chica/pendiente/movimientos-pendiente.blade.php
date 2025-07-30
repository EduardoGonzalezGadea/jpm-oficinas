<div>
    <div class="row">
        <div class="col-md-12">

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
                                                id="movimiento_monto_rendido" wire:model.debounce.300ms="rendido" step="0.01"
                                                min="0" placeholder="0.00">
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
                                                id="movimiento_monto_reintegrado" wire:model.lazy="reintegrado" step="0.01"
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
        });
    </script>

    {{-- Estilos adicionales --}}
    <style>
        .table td {
            vertical-align: middle;
        }
        .modal-lg {
            max-width: 900px;
        }

        .modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 3.5rem);
            overflow: hidden;
        }

        .modal-dialog-scrollable .modal-body {
            overflow-y: auto;
            max-height: calc(100vh - 210px);
        }

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
