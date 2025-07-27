{{-- resources/views/livewire/tesoreria/caja-chica/modal-nuevo-fondo.blade.php --}}
<div>
    @if ($mostrarModal)
        <div class="modal fade show d-block" id="modalNuevoFondo" tabindex="-1" role="dialog"
            style="background-color: rgba(0,0,0,0.5);" aria-modal="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form wire:submit.prevent="guardar">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-comment-dollar"></i>
                                Nuevo Fondo Permanente
                            </h5>
                            <button type="button" class="close text-white" wire:click="cerrarModal"
                                aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            @if (session()->has('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}<button type="button" class="close" data-dismiss="alert"
                                        aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                            @endif
                            @if (session()->has('message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('message') }}<button type="button" class="close" data-dismiss="alert"
                                        aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                            @endif
                            <div class="form-group"><label for="fondoMes">Mes:</label>
                                <select class="form-control @error('mes') is-invalid @enderror" id="fondoMes"
                                    wire:model.defer="mes" required autofocus>
                                    <option value="">Seleccionar Mes...</option>
                                    <option value="enero">Enero</option>
                                    <option value="febrero">Febrero</option>
                                    <option value="marzo">Marzo</option>
                                    <option value="abril">Abril</option>
                                    <option value="mayo">Mayo</option>
                                    <option value="junio">Junio</option>
                                    <option value="julio">Julio</option>
                                    <option value="agosto">Agosto</option>
                                    <option value="setiembre">Setiembre</option>
                                    <option value="octubre">Octubre</option>
                                    <option value="noviembre">Noviembre</option>
                                    <option value="diciembre">Diciembre</option>
                                </select>
                                @error('mes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group"><label for="fondoAnio">Año:</label><input type="number"
                                    class="form-control @error('anio') is-invalid @enderror" id="fondoAnio"
                                    wire:model.defer="anio" required min="2000" max="2100">
                                @error('anio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group"><label for="fondoMonto">Monto:</label><input type="number"
                                    step="0.01" class="form-control @error('monto') is-invalid @enderror"
                                    id="fondoMonto" wire:model.defer="monto" required min="0">
                                @error('monto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModal">Cancelar</button>
                            <button type="submit" class="btn btn-success" wire:loading.attr="disabled"><span
                                    wire:loading.remove>Guardar Fondo</span><span
                                    wire:loading>Guardando...</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('actualizar-modal-fondo', function(data) {});
        });
    </script>
</div>
