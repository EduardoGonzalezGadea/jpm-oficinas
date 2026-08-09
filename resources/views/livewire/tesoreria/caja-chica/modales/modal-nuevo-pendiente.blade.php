<div>
  @if ($mostrarModal)
  <div class="modal fade" id="modalNuevoPendiente" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog">
      <div class="modal-content">
        <form wire:submit="guardar">
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

            <input type="hidden" wire:model.live="idCajaChica">

            <div class="form-group">
              <label for="pendienteNumero">Número:</label>
              <input type="number" class="form-control @error('pendiente') is-invalid @enderror"
                id="pendienteNumero" wire:model="pendiente" required>
              @error('pendiente')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="pendienteFecha">Fecha:</label>
              <input type="date"
                class="form-control @error('fechaPendientes') is-invalid @enderror"
                id="pendienteFecha" wire:model="fechaPendientes" required
                value="{{ now()->format('Y-m-d') }}">
              @error('fechaPendientes')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="pendienteDependencia">Dependencia:</label>
              <select class="form-control @error('relDependencia') is-invalid @enderror"
                id="pendienteDependencia" wire:model="relDependencia" required x-data x-init="(() => {
                    const targetElement = $el;
                    let focusAttempts = 0;
                    const maxFocusAttempts = 5;

                    function attemptFocus() {
                      if (focusAttempts < maxFocusAttempts) {
                        targetElement.focus();
                        if (document.activeElement !== targetElement) {
                          focusAttempts++;
                          requestAnimationFrame(attemptFocus);
                        }
                      }
                    }

                    setTimeout(() => { requestAnimationFrame(attemptFocus); }, 100);
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
                id="pendienteMonto" wire:model="montoPendientes" required>
              @error('montoPendientes')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="guardar">
              <span wire:loading.class="d-none" wire:target="guardar">Otorgar Pendiente</span>
              <span wire:loading.class.remove="d-none" wire:target="guardar" class="d-none">Guardando...</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif
</div>