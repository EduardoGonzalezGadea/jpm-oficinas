{{-- resources/views/livewire/tesoreria/caja-chica/modal-nuevo-pago.blade.php --}}
<div>
    @if($mostrarModal)
        <div class="modal fade show d-block" id="modalNuevoPago" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content" style="max-height: 95vh; display: flex; flex-direction: column;">
                    <form wire:submit.prevent="guardar">
                        <div class="modal-header bg-info text-white flex-shrink-0">
                            <h5 class="modal-title">
                                 <i class="far fa-handshake"></i>
                                Nuevo Pago Directo
                            </h5>
                            <button type="button" class="close text-white" wire:click="cerrarModal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="flex-grow: 1; overflow-y: auto; max-height: 70vh;">
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
                                <input type="date" class="form-control @error('fechaEgresoPagos') is-invalid @enderror"
                                       id="pagoFechaEgreso" wire:model.defer="fechaEgresoPagos" required>
                                @error('fechaEgresoPagos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="pagoEgreso">Egreso:</label>
                                <input type="text" class="form-control @error('egresoPagos') is-invalid @enderror"
                                       id="pagoEgreso" wire:model.defer="egresoPagos" required autofocus>
                                @error('egresoPagos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="pagoAcreedor">Acreedor:</label>
                                <select class="form-control @error('relAcreedores') is-invalid @enderror"
                                        id="pagoAcreedor" wire:model.defer="relAcreedores">
                                    <option value="">Seleccionar Acreedor (Opcional)...</option>
                                    @foreach($acreedores as $acreedor)
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
                                <input type="number" step="0.01" class="form-control @error('montoPagos') is-invalid @enderror"
                                       id="pagoMonto" wire:model.defer="montoPagos" required min="0.01">
                                @error('montoPagos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer flex-shrink-0">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-info" wire:loading.attr="disabled">
                                <span wire:loading.remove>Pagar</span>
                                <span wire:loading>Procesando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('actualizar-modal-pago', function (data) {
            });
        });
    </script>
</div>