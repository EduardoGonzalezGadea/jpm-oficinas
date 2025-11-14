<div>
    <form wire:submit.prevent="save">
        <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" wire:model="nombre" class="form-control" id="nombre" placeholder="Ingrese el nombre del banco">
            @error('nombre') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="codigo">Código</label>
            <input type="text" wire:model="codigo" class="form-control" id="codigo" placeholder="Ingrese el código del banco">
            @error('codigo') <span class="text-danger">{{ $message }}</span> @enderror
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
