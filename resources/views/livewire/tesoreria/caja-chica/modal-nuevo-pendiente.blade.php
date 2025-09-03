<div>
    @if ($mostrarModal)
        <div class="modal fade" id="modalNuevoPendiente" tabindex="-1" wire:ignore.self>
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit.prevent="guardar">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-money-bill"></i>
                                Nuevo Pendiente
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            @if (session()->has('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if (session()->has('message'))
                                <div class="alert alert-success">
                                    {{ session('message') }}
                                </div>
                            @endif

                            <input type="hidden" wire:model="idCajaChica">

                            <div class="form-group">
                                <label for="pendienteNumero">Número:</label>
                                <input type="number" class="form-control @error('pendiente') is-invalid @enderror"
                                    id="pendienteNumero" wire:model.defer="pendiente" required>
                                @error('pendiente')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="pendienteFecha">Fecha:</label>
                                <input type="date"
                                    class="form-control @error('fechaPendientes') is-invalid @enderror"
                                    id="pendienteFecha" wire:model.defer="fechaPendientes" required
                                    value="{{ now()->format('Y-m-d') }}">
                                @error('fechaPendientes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="pendienteDependencia">Dependencia:</label>
                                <select class="form-control @error('relDependencia') is-invalid @enderror"
                                    id="pendienteDependencia" wire:model.defer="relDependencia" required x-data x-init="(() => {
                                        const targetElement = $el;
                                        let focusAttempts = 0;
                                        const maxFocusAttempts = 5; // Try for a short period

                                        function attemptFocus() {
                                            if (focusAttempts < maxFocusAttempts) {
                                                targetElement.focus();
                                                console.log('Attempting focus on pendienteDependencia. Active element:', document.activeElement);
                                                if (document.activeElement !== targetElement) {
                                                    focusAttempts++;
                                                    requestAnimationFrame(attemptFocus); // Try again on next frame
                                                } else {
                                                    console.log('Focus achieved and maintained on pendienteDependencia!');
                                                }
                                            }
                                        } else {
                                            console.log('Max focus attempts reached for pendienteDependencia. Focus not maintained.');
                                        }

                                        // Start the aggressive focus attempt after a short initial delay
                                        setTimeout(() => {
                                            requestAnimationFrame(attemptFocus);
                                        }, 100); // Initial delay to let other scripts run first
                                    })()">
                                    <option value="">Seleccionar...</option>
                                    @foreach ($dependencias as $dep)
                                        <option value="{{ $dep->idDependencias }}">{{ $dep->dependencia }}</option>
                                    @endforeach
                                </select>
                                @error('relDependencia')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="pendienteMonto">Monto:</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('montoPendientes') is-invalid @enderror"
                                    id="pendienteMonto" wire:model.defer="montoPendientes" required>
                                @error('montoPendientes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Otorgar Pendiente
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
            $(document).on('hidden.bs.modal', '#modalNuevoPendiente', function() {
                @this.cerrarModal();
            });
        });
    </script>
</div>
