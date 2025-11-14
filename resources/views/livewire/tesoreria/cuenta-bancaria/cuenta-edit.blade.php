<div>
    <form wire:submit.prevent="save">
        <div class="form-group">
            <label for="banco_id">Banco</label>
            <select wire:model="banco_id" class="form-control" id="banco_id" required>
                <option value="">Seleccionar banco...</option>
                @foreach($bancos as $banco)
                    <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                @endforeach
            </select>
            @error('banco_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="numero_cuenta">Número de Cuenta</label>
            <input type="text" wire:model="numero_cuenta" class="form-control" id="numero_cuenta" placeholder="Ingrese el número de cuenta" required>
            @error('numero_cuenta') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="tipo">Tipo de Cuenta</label>
            <select wire:model="tipo" class="form-control" id="tipo" required>
                <option value="Corriente">Corriente</option>
                <option value="Ahorro">Ahorro</option>
                <option value="Plazo Fijo">Plazo Fijo</option>
            </select>
            @error('tipo') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" wire:model="activa" id="activa" value="1" {{ $activa ? 'checked' : '' }}>
                <label class="custom-control-label" for="activa">Activa</label>
            </div>
        </div>
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea wire:model="observaciones" class="form-control" id="observaciones" rows="3" placeholder="Observaciones opcionales"></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </div>
    </form>
</div>
