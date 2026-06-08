{{-- resources/views/livewire/tesoreria/caja-chica/modal-nuevo-pago.blade.php --}}
<div>
    @if ($mostrarModal)
    <div class="modal fade" id="modalNuevoPago" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="guardar">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="far fa-handshake"></i>
                            Nuevo Pago Directo
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif

                        @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif

                        <input type="hidden" wire:model="idCajaChica">

                        <div class="form-group">
                            <label for="pagoFechaEgreso">Fecha Egreso:</label>
                            <input type="date"
                                class="form-control @error('fechaEgresoPagos') is-invalid @enderror"
                                id="pagoFechaEgreso" wire:model.defer="fechaEgresoPagos" required>
                            @error('fechaEgresoPagos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="pagoEgreso">Egreso (Opcional):</label>
                            <input type="text" class="form-control @error('egresoPagos') is-invalid @enderror"
                                id="pagoEgreso" wire:model.defer="egresoPagos" autofocus
                                placeholder="Número de egreso (opcional)">
                            @error('egresoPagos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="pagoAcreedor">Acreedor:</label>
                            <select class="form-control @error('relAcreedores') is-invalid @enderror"
                                id="pagoAcreedor" wire:model.defer="relAcreedores">
                                <option value="">Seleccionar Acreedor...</option>
                                @foreach ($acreedores as $acreedor)
                                <option value="{{ $acreedor->idAcreedores }}">{{ $acreedor->acreedor }}</option>
                                @endforeach
                            </select>
                            @error('relAcreedores')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="pagoConcepto">Concepto:</label>
                            <input type="text" class="form-control @error('conceptoPagos') is-invalid @enderror"
                                id="pagoConcepto" wire:model.defer="conceptoPagos" required>
                            @error('conceptoPagos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="pagoMonto">Monto:</label>
                            <input type="number" step="0.01"
                                class="form-control @error('montoPagos') is-invalid @enderror" id="pagoMonto"
                                wire:model.defer="montoPagos" required min="0.01">
                            @error('montoPagos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-info" wire:loading.attr="disabled" wire:target="guardar">
                            <span wire:loading.class="d-none" wire:target="guardar">Pagar</span>
                            <span wire:loading.class.remove="d-none" wire:target="guardar" class="d-none">Procesando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:init', function() {
            // Manejar el cierre del modal con Bootstrap y sincronizar con Livewire
            $(document).on('hidden.bs.modal', '#modalNuevoPago', function() {
                @this.cerrarModal();
            });

            $('#modalNuevoPago').on('shown.bs.modal', function() {
                $('#pagoEgreso').focus();

                const form = document.querySelector('#modalNuevoPago form');
                const focusable = Array.from(form.querySelectorAll('input, select, button[type="submit"]'));

                form.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                        e.preventDefault();
                        const currentIndex = focusable.indexOf(e.target);
                        const nextElement = focusable[currentIndex + 1];

                        if (nextElement) {
                            nextElement.focus();
                        }
                    }
                });
            });
        });
    </script>
</div>