<div>
    @if($mostrarModal)
    <div class="modal fade show d-block" id="modalNuevoPendiente" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="guardar">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-money-bill"></i>
                            Nuevo Pendiente
                        </h5>
                        <button type="button" class="close text-white" wire:click="cerrarModal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="flex-grow: 1; overflow-y: auto; max-height: 70vh;">
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
                            <input type="date" class="form-control @error('fechaPendientes') is-invalid @enderror" 
                                   id="pendienteFecha" wire:model.defer="fechaPendientes" required value="{{ now()->format('Y-m-d') }}">
                            @error('fechaPendientes') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="pendienteDependencia">Dependencia:</label>
                            <select class="form-control @error('relDependencia') is-invalid @enderror" 
                                    id="pendienteDependencia" wire:model.defer="relDependencia" required autofocus>
                                <option value="">Seleccionar...</option>
                                @foreach($dependencias as $dep)
                                    <option value="{{ $dep->idDependencias }}">{{ $dep->dependencia }}</option>
                                @endforeach
                            </select>
                            @error('relDependencia') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="pendienteMonto">Monto:</label>
                            <input type="number" step="0.01" class="form-control @error('montoPendientes') is-invalid @enderror" 
                                   id="pendienteMonto" wire:model.defer="montoPendientes" required>
                            @error('montoPendientes') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
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
    @endif {{-- Asegúrate de que este @endif esté presente --}}

    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('actualizar-modal-pendiente', function (data) {
                // Opcional: Puedes usar esto si necesitas acciones JS adicionales
                // cuando se emite el evento, aunque la visibilidad ya la controla Livewire.
                // if (data.mostrar) { ... } else { ... }
            });
        });
    </script>
</div> {{-- Cierre de la etiqueta div principal --}}