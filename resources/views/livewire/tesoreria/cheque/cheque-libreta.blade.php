<div>
    <form wire:submit.prevent="save" id="cheque-libreta-form">
        <!-- Fila completa para banco y cuenta -->
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="cuenta_bancaria_id">Cuenta Bancaria</label>
                    <select wire:model="cuenta_bancaria_id" class="form-control @error('cuenta_bancaria_id') is-invalid @enderror">
                        <option value="">Seleccionar cuenta...</option>
                        @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}">{{ $cuenta->banco->nombre }} - {{ $cuenta->numero_cuenta }}</option>
                        @endforeach
                    </select>
                    @error('cuenta_bancaria_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Fila con tres columnas para inicial, cantidad y final -->
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="serie">Serie</label>
                    <input type="text" wire:model="serie" class="form-control @error('serie') is-invalid @enderror" id="serie" placeholder="Serie del cheque" maxlength="11" wire:key="serie-input">
                    @error('serie')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="inicio">Número Inicial</label>
                    <input type="number" wire:model.live="inicio" class="form-control @error('inicio') is-invalid @enderror" placeholder="1, 26, 51..." wire:key="inicio-input">
                    @error('inicio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="cantidad_libretas">Cantidad de Libretas</label>
                    <input type="number" wire:model.live="cantidad_libretas" class="form-control @error('cantidad_libretas') is-invalid @enderror" placeholder="1, 2, 3...">
                    @error('cantidad_libretas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Número Final</label>
                    <input type="number" class="form-control" wire:model="numero_final" readonly placeholder="Calculado automáticamente">
                </div>
            </div>
        </div>


        <div class="form-group d-none">
            <button type="submit" class="btn btn-primary" id="submit-button">
                <i class="fas fa-save mr-2"></i>Registrar Cheques
            </button>
        </div>

        <div class="alert alert-info mt-3 mb-3">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Reglas para registrar libretas de cheques:</strong>
            <ul class="mb-0 mt-2">
                <li>Cada libreta contiene 25 cheques</li>
                <li>Los números iniciales válidos son: 1, 26, 51, 76, 101, 126, etc.</li>
                <li>El número final se calcula automáticamente según el inicial y la cantidad de libretas</li>
                <li>Ejemplos: 1 inicial + 1 libreta = final 25, 26 inicial + 2 libretas = final 75</li>
            </ul>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button wire:click="saveDirect" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Registrar Cheques
            </button>
        </div>
    </form>
</div>
