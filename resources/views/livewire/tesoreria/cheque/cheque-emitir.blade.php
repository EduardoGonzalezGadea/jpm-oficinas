<div>
    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <div class="card border-0">
        <div class="card-body px-1 py-0 m-0">
            <!-- Layout responsive: columnas en pantallas medianas y superiores -->
            <div class="row">
                <!-- Columna Izquierda: Cheques Disponibles -->
                <div class="col-lg-6">
                    <h5 class="mb-1">
                        <i class="fas fa-money-check mr-2"></i>Cheques Disponibles
                    </h5>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <input type="text" wire:model.debounce.300ms="search" class="form-control form-control-sm" placeholder="Buscar por número de cheque o banco...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" wire:click="clearSearch">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($cheques->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm" style="font-size: 0.875rem;">
                            <thead class="thead-dark" style="font-size: 0.8125rem;">
                                <tr>
                                    <th class="text-nowrap py-1">Banco</th>
                                    <th class="text-nowrap py-1">Cuenta</th>
                                    <th class="text-nowrap py-1">Serie</th>
                                    <th class="text-nowrap py-1">Número</th>
                                    <th class="text-nowrap py-1 d-print-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cheques as $cheque)
                                <tr style="font-size: 0.875rem;">
                                    <td class="text-nowrap py-1">{{ $cheque->cuentaBancaria->banco->codigo }}</td>
                                    <td class="text-nowrap py-1">{{ $cheque->cuentaBancaria->numero_cuenta }}</td>
                                    <td class="text-nowrap py-1">{{ $cheque->serie }}</td>
                                    <td class="text-nowrap py-1">{{ $cheque->numero_cheque }}</td>
                                    <td class="text-nowrap py-1 d-print-none">
                                        <button class="btn btn-sm btn-primary btn-xs mr-1 py-0 px-1" wire:click.prevent="openEmitirModal({{ $cheque->id }})" data-toggle="modal" data-target="#emitirChequeModal" title="Emitir cheque">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-xs py-0 px-1" wire:click.prevent="openAnularModal({{ $cheque->id }})" data-toggle="modal" data-target="#anularChequeModal" title="Anular cheque">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-2">
                        {{ $cheques->links() }}
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>No se encontraron cheques disponibles.
                    </div>
                    @endif
                </div>

                <!-- Columna Derecha: Cheques Emitidos -->
                <div class="col-lg-6">
                    <h5 class="mb-1">
                        <i class="fas fa-check-circle mr-2"></i>Cheques Emitidos (sin planilla)
                    </h5>

                    @if($chequesEmitidos && $chequesEmitidos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm" style="font-size: 0.875rem;">
                            <thead class="thead-dark" style="font-size: 0.8125rem;">
                                <tr>
                                    {{-- <th class="text-nowrap py-2 text-center thead-dark align-middle">
                                            <input type="checkbox"
                                                   wire:model="selectAll"
                                                   class="form-check-input">
                                        </th> --}}
                                    <th class="text-nowrap py-1 align-middle">
                                        <input type="checkbox" wire:model="selectAll">
                                    </th>
                                    <th class="text-nowrap py-1">Banco</th>
                                    <th class="text-nowrap py-1">Cuenta</th>
                                    <th class="text-nowrap py-1">Serie</th>
                                    <th class="text-nowrap py-1">Número</th>
                                    <th class="text-nowrap py-1">Monto</th>
                                    <th class="text-nowrap py-1">Fecha Emisión</th>
                                    <th class="text-nowrap py-1">Beneficiario</th>
                                    <th class="text-nowrap py-1 text-center d-print-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chequesEmitidos as $cheque)
                                <tr style="font-size: 0.875rem;">
                                    <td class="text-nowrap py-1 text-center">
                                        <input type="checkbox"
                                            wire:model="selectedCheques"
                                            value="{{ $cheque->id }}">
                                    </td>
                                    <td class="text-nowrap py-1">{{ $cheque->cuentaBancaria->banco->codigo }}</td>
                                    <td class="text-nowrap py-1">{{ $cheque->cuentaBancaria->numero_cuenta }}</td>
                                    <td class="text-nowrap py-1">{{ $cheque->serie }}</td>
                                    <td class="text-nowrap py-1"><strong>{{ $cheque->numero_cheque }}</strong></td>
                                    <td class="text-nowrap py-1 text-right">${{ number_format($cheque->monto, 2, ',', '.') }}</td>
                                    <td class="text-nowrap py-1">{{ \Carbon\Carbon::parse($cheque->fecha_emision)->format('d/m/Y') }}</td>
                                    <td class="text-nowrap py-1">{{ $cheque->beneficiario }}</td>
                                    <td class="text-nowrap py-1 text-center d-print-none">
                                        <button class="btn btn-sm btn-warning btn-xs py-0 px-1 mr-1" wire:click.prevent="openEditarModal({{ $cheque->id }})" data-toggle="modal" data-target="#editarChequeModal" title="Editar cheque">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-xs py-0 px-1" wire:click.prevent="openAnularModal({{ $cheque->id }})" data-toggle="modal" data-target="#anularChequeModal" title="Anular cheque">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Resumen de Totales y Acción -->
                    <div class="card bg-light mt-3">
                        <div class="card-body py-1">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="row text-center">
                                        <div class="col-6 px-1">
                                            <small class="d-block" style="font-size: 0.75rem;">Total Emitidos</small>
                                            <div class="h6 mb-0 text-primary font-weight-bold">${{ number_format($this->totalEmitidos, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="col-6 px-1">
                                            <small class="d-block" style="font-size: 0.75rem;">Seleccionados</small>
                                            <div class="h6 mb-0 text-primary font-weight-bold">${{ number_format($this->totalSeleccionados, 0, ',', '.') }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-right">
                                    <button type="button"
                                        wire:click="formarPlanilla"
                                        class="btn btn-success btn-sm py-1 px-2"
                                        {{ empty($selectedCheques) ? 'disabled' : '' }}>
                                        <i class="fas fa-plus-circle mr-1" style="font-size: 0.875rem;"></i>
                                        <small>Formar Planilla</small>
                                        <span class="badge badge-light ml-1">{{ count($selectedCheques) }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>No hay cheques emitidos sin planilla asignada.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para emitir cheque -->
    <div wire:ignore.self class="modal fade" id="emitirChequeModal" tabindex="-1" role="dialog" aria-labelledby="emitirChequeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header text-white bg-primary py-2">
                    <h5 class="modal-title h6" id="emitirChequeModalLabel">
                        <i class="fas fa-paper-plane mr-2"></i>Emitir Cheque
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    @if($selectedCheque)
                    <div class="d-flex justify-content-between align-items-center mb-2 py-1 bg-light rounded px-2">
                        <small class="mb-0">
                            <i class="fas fa-university mr-1"></i>{{ $selectedCheque['cuenta_bancaria']['banco']['nombre'] }}
                        </small>
                        <small class="mb-0">
                            <i class="fas fa-credit-card mr-1"></i>{{ $selectedCheque['cuenta_bancaria']['numero_cuenta'] }}
                        </small>
                        <small class="mb-0">
                            <i class="fas fa-barcode mr-1"></i>Serie: {{ $selectedCheque['serie'] ?? 'SIN DATO' }} - N°: {{ $selectedCheque['numero_cheque'] }}
                        </small>
                    </div>
                    @endif

                    <form wire:submit.prevent="emitir">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="fecha_emision" class="mb-1 small">Fecha *</label>
                                    <input type="date" wire:model="fecha_emision" class="form-control form-control-sm @error('fecha_emision') is-invalid @enderror" id="fecha_emision">
                                    @error('fecha_emision')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="monto" class="mb-1 small">Monto *</label>
                                    <input type="number" step="0.01" wire:model="monto" class="form-control form-control-sm @error('monto') is-invalid @enderror" id="monto" placeholder="Monto">
                                    @error('monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label for="beneficiario" class="mb-1 small">Beneficiario *</label>
                            <input type="text" wire:model.debounce.300ms="beneficiario" class="form-control form-control-sm @error('beneficiario') is-invalid @enderror" id="beneficiario" placeholder="Nombre del beneficiario" autocomplete="off">
                            @if(!empty($beneficiariosSugerencias))
                            <div class="list-group position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach($beneficiariosSugerencias as $sugerencia)
                                <button type="button" class="list-group-item list-group-item-action py-1 px-2 small" wire:click="seleccionarBeneficiario('{{ addslashes($sugerencia) }}')">
                                    {{ $sugerencia }}
                                </button>
                                @endforeach
                            </div>
                            @endif
                            @error('beneficiario')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-2">
                            <label for="concepto" class="mb-1 small">Concepto *</label>
                            <textarea wire:model.debounce.300ms="concepto" class="form-control form-control-sm @error('concepto') is-invalid @enderror" id="concepto" rows="2" placeholder="Concepto del cheque"></textarea>
                            @if(!empty($conceptosSugerencias))
                            <div class="list-group position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach($conceptosSugerencias as $sugerencia)
                                <button type="button" class="list-group-item list-group-item-action py-1 px-2 small" wire:click="seleccionarConcepto('{{ addslashes($sugerencia) }}')">
                                    {{ $sugerencia }}
                                </button>
                                @endforeach
                            </div>
                            @endif
                            @error('concepto')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary btn-xs" data-dismiss="modal">Cancelar</button>
                    <button type="button" wire:click.prevent="emitir()" class="btn btn-sm btn-primary btn-xs">
                        <i class="fas fa-paper-plane mr-1"></i>Emitir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar cheque -->
    <div wire:ignore.self class="modal fade" id="editarChequeModal" tabindex="-1" role="dialog" aria-labelledby="editarChequeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title h6" id="editarChequeModalLabel">
                        <i class="fas fa-edit mr-2"></i>Editar Cheque Emitido
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    @if($selectedChequeEditar)
                    <div class="d-flex justify-content-between align-items-center mb-2 py-1 bg-light rounded px-2">
                        <small class="mb-0">
                            <i class="fas fa-university mr-1"></i>{{ $selectedChequeEditar['cuenta_bancaria']['banco']['nombre'] }}
                        </small>
                        <small class="mb-0">
                            <i class="fas fa-credit-card mr-1"></i>{{ $selectedChequeEditar['cuenta_bancaria']['numero_cuenta'] }}
                        </small>
                        <small class="mb-0">
                            <i class="fas fa-barcode mr-1"></i>Serie: {{ $selectedChequeEditar['serie'] ?? 'SIN DATO' }} - N°: {{ $selectedChequeEditar['numero_cheque'] }}
                        </small>
                    </div>
                    @endif

                    <form wire:submit.prevent="editar">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="edit_fecha_emision" class="mb-1 small">Fecha *</label>
                                    <input type="date" wire:model="edit_fecha_emision" class="form-control form-control-sm @error('edit_fecha_emision') is-invalid @enderror" id="edit_fecha_emision">
                                    @error('edit_fecha_emision')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="edit_monto" class="mb-1 small">Monto *</label>
                                    <input type="number" step="0.01" wire:model="edit_monto" class="form-control form-control-sm @error('edit_monto') is-invalid @enderror" id="edit_monto" placeholder="Monto">
                                    @error('edit_monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label for="edit_beneficiario" class="mb-1 small">Beneficiario *</label>
                            <input type="text" wire:model.debounce.300ms="edit_beneficiario" class="form-control form-control-sm @error('edit_beneficiario') is-invalid @enderror" id="edit_beneficiario" placeholder="Nombre del beneficiario" autocomplete="off">
                            @if(!empty($beneficiariosSugerencias))
                            <div class="list-group position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach($beneficiariosSugerencias as $sugerencia)
                                <button type="button" class="list-group-item list-group-item-action py-1 px-2 small" wire:click="seleccionarBeneficiario('{{ addslashes($sugerencia) }}')">
                                    {{ $sugerencia }}
                                </button>
                                @endforeach
                            </div>
                            @endif
                            @error('edit_beneficiario')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-2">
                            <label for="edit_concepto" class="mb-1 small">Concepto *</label>
                            <textarea wire:model.debounce.300ms="edit_concepto" class="form-control form-control-sm @error('edit_concepto') is-invalid @enderror" id="edit_concepto" rows="2" placeholder="Concepto del cheque"></textarea>
                            @if(!empty($conceptosSugerencias))
                            <div class="list-group position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach($conceptosSugerencias as $sugerencia)
                                <button type="button" class="list-group-item list-group-item-action py-1 px-2 small" wire:click="seleccionarConcepto('{{ addslashes($sugerencia) }}')">
                                    {{ $sugerencia }}
                                </button>
                                @endforeach
                            </div>
                            @endif
                            @error('edit_concepto')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary btn-xs" data-dismiss="modal">Cancelar</button>
                    <button type="button" wire:click.prevent="editar()" class="btn btn-sm btn-warning btn-xs">
                        <i class="fas fa-save mr-1"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para anular cheque -->
    <div wire:ignore.self class="modal fade" id="anularChequeModal" tabindex="-1" role="dialog" aria-labelledby="anularChequeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header text-white bg-danger py-2">
                    <h5 class="modal-title h6" id="anularChequeModalLabel">
                        <i class="fas fa-ban mr-2"></i>Anular Cheque
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body py-2">
                    @if($selectedChequeAnular)
                    <div class="d-flex justify-content-between align-items-center mb-2 py-1 bg-light rounded px-2">
                        <small class="mb-0">
                            <i class="fas fa-university mr-1"></i>{{ $selectedChequeAnular['cuenta_bancaria']['banco']['nombre'] }}
                        </small>
                        <small class="mb-0">
                            <i class="fas fa-credit-card mr-1"></i>{{ $selectedChequeAnular['cuenta_bancaria']['numero_cuenta'] }}
                        </small>
                        <small class="mb-0">
                            <i class="fas fa-barcode mr-1"></i>Serie: {{ $selectedChequeAnular['serie'] ?? 'SIN DATO' }} - N°: {{ $selectedChequeAnular['numero_cheque'] }}
                        </small>
                    </div>
                    @endif

                    <form wire:submit.prevent="anular" id="anularForm">
                        <div class="form-group mb-2">
                            <label for="motivo_anulacion" class="mb-1 small">Motivo de anulación *</label>
                            <textarea wire:model.lazy="motivo_anulacion" class="form-control form-control-sm @error('motivo_anulacion') is-invalid @enderror" id="motivo_anulacion" rows="3" placeholder="Motivo de la anulación del cheque"></textarea>
                            @error('motivo_anulacion')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary btn-xs" data-dismiss="modal">Cancelar</button>
                    <button type="button" wire:click.prevent="anular()" class="btn btn-sm btn-danger btn-xs" onclick="this.disabled=true; this.innerHTML='<i class=\'fas fa-spinner fa-spin mr-1\'></i>Procesando...';">
                        <i class="fas fa-ban mr-1"></i>Anular
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.livewire.on('showEmitirModal', () => {
            $('#emitirChequeModal').modal('show');
        });

        window.livewire.on('hideEmitirModal', () => {
            $('#emitirChequeModal').modal('hide');
        });

        window.livewire.on('showAnularModal', () => {
            $('#anularChequeModal').modal('show');
        });

        window.livewire.on('hideAnularModal', () => {
            $('#anularChequeModal').modal('hide');
            // Re-enable button and restore text after modal closes
            setTimeout(() => {
                const button = $('#anularChequeModal .btn-danger');
                button.prop('disabled', false);
                button.html('<i class="fas fa-ban mr-1"></i>Anular');
            }, 500);
        });

        window.livewire.on('chequeEmitido', () => {
            $('#emitirChequeModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Cheque emitido con éxito!',
                showConfirmButton: false,
                timer: 1500
            });
        });

        window.livewire.on('chequeAnulado', () => {
            $('#anularChequeModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Cheque anulado con éxito!',
                showConfirmButton: false,
                timer: 1500
            });
        });

        window.livewire.on('showEditarModal', () => {
            $('#editarChequeModal').modal('show');
        });

        window.livewire.on('hideEditarModal', () => {
            $('#editarChequeModal').modal('hide');
        });

        window.livewire.on('chequeEditado', () => {
            $('#editarChequeModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Cheque editado con éxito!',
                showConfirmButton: false,
                timer: 1500
            });
        });

        $(document).ready(function() {
            $('#emitirChequeModal').on('shown.bs.modal', function() {
                $('#monto').focus();

                const form = $(this).find('form');
                const inputs = form.find('input:not([type="hidden"]), textarea, select');

                inputs.off('keydown').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        const currentIndex = inputs.index(this);
                        const nextIndex = currentIndex + 1;

                        if (nextIndex < inputs.length) {
                            $(inputs[nextIndex]).focus();
                        } else {
                            form.closest('.modal-content').find('.btn-primary').focus();
                        }
                    }
                });
            });

            $('#emitirChequeModal').on('hidden.bs.modal', function() {
                window.livewire.emit('closeModal');
            });

            $('#anularChequeModal').on('shown.bs.modal', function() {
                $('#motivo_anulacion').focus();

                const form = $(this).find('form');
                const inputs = form.find('input:not([type="hidden"]), textarea, select');

                inputs.off('keydown').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        const currentIndex = inputs.index(this);
                        const nextIndex = currentIndex + 1;

                        if (nextIndex < inputs.length) {
                            $(inputs[nextIndex]).focus();
                        } else {
                            form.closest('.modal-content').find('.btn-danger').focus();
                        }
                    }
                });
            });

            $('#anularChequeModal').on('hidden.bs.modal', function() {
                window.livewire.emit('closeAnularModal');
            });

            $('#editarChequeModal').on('shown.bs.modal', function() {
                $('#edit_monto').focus();

                const form = $(this).find('form');
                const inputs = form.find('input:not([type="hidden"]), textarea, select');

                inputs.off('keydown').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        const currentIndex = inputs.index(this);
                        const nextIndex = currentIndex + 1;

                        if (nextIndex < inputs.length) {
                            $(inputs[nextIndex]).focus();
                        } else {
                            form.closest('.modal-content').find('.btn-warning').focus();
                        }
                    }
                });
            });

            $('#editarChequeModal').on('hidden.bs.modal', function() {
                window.livewire.emit('closeEditarModal');
            });
        });

        // Listener para redireccionamiento después de éxito
        window.addEventListener('redirect-after-success', event => {
            setTimeout(() => {
                window.location.href = event.detail.url;
            }, event.detail.delay || 1000);
        });
    </script>
    @endpush
</div>
