<div>
  <div class="card">
    <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><strong><i class="fas fa-landmark mr-2"></i>Cuentas Bancarias</strong></h4>
      <button wire:click="create" type="button" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Nueva Cuenta
      </button>
    </div>
    <div class="card-body px-2">
      <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
               class="form-control" placeholder="Buscar por banco o número de cuenta...">
      </div>

      @if($cuentas->count() > 0)
        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead>
              <tr>
                <th class="align-middle">Banco</th>
                <th class="align-middle">Número de Cuenta</th>
                <th class="align-middle">Tipo</th>
                <th class="text-center align-middle">Activa</th>
                <th class="align-middle">Observaciones</th>
                <th class="text-center align-middle">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($cuentas as $cuenta)
                <tr wire:key="cuenta-row-{{ $cuenta->id }}">
                  <td class="align-middle">{{ $cuenta->banco->nombre ?? '—' }}</td>
                  <td class="align-middle">{{ $cuenta->numero_cuenta }}</td>
                  <td class="align-middle">{{ $cuenta->tipo }}</td>
                  <td class="text-center align-middle">
                    @if($cuenta->activa)
                      <span class="badge badge-success">Sí</span>
                    @else
                      <span class="badge badge-secondary">No</span>
                    @endif
                  </td>
                  <td class="align-middle">{{ $cuenta->observaciones ?? '—' }}</td>
                  <td class="text-center align-middle">
                    <button wire:click="edit({{ $cuenta->id }})"
                            class="btn btn-sm btn-primary" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: 'Se eliminará la cuenta bancaria.', method: 'destroy', id: {{ $cuenta->id }}, confirmButtonText: 'Sí, eliminar' } }))"
                      class="btn btn-sm btn-danger" title="Eliminar">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-center">
          {{ $cuentas->links() }}
        </div>
      @else
        <div class="alert alert-info">
          <i class="fas fa-info-circle mr-1"></i>
          No hay cuentas bancarias registradas aún.
        </div>
      @endif
    </div>
  </div>

  <!-- Modal Cuenta Bancaria -->
  <div wire:ignore.self wire:key="cuenta-modal" class="modal fade" id="cuentaModal"
       tabindex="-1" role="dialog" aria-labelledby="cuentaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cuentaModalLabel">
            {{ $cuentaId ? 'Editar Cuenta Bancaria' : 'Nueva Cuenta Bancaria' }}
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="cuenta_banco_id">Banco *</label>
            <select class="form-control @error('banco_id') is-invalid @enderror"
                    wire:model="banco_id" id="cuenta_banco_id">
              <option value="">— Seleccionar banco —</option>
              @foreach($bancos as $banco)
                <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
              @endforeach
            </select>
            @error('banco_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group">
            <label for="cuenta_numero">Número de Cuenta *</label>
            <input type="text"
                   class="form-control @error('numero_cuenta') is-invalid @enderror"
                   wire:model="numero_cuenta" id="cuenta_numero">
            @error('numero_cuenta')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group">
            <label for="cuenta_tipo">Tipo *</label>
            <select class="form-control @error('tipo') is-invalid @enderror"
                    wire:model="tipo" id="cuenta_tipo">
              <option value="Corriente">Corriente</option>
              <option value="Ahorro">Ahorro</option>
              <option value="Recaudación">Recaudación</option>
            </select>
            @error('tipo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group">
            <label for="cuenta_observaciones">Observaciones</label>
            <textarea class="form-control" wire:model="observaciones"
                      id="cuenta_observaciones" rows="2"></textarea>
          </div>
          <div class="form-group">
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input"
                     wire:model="activa" id="cuenta_activa">
              <label class="custom-control-label" for="cuenta_activa">Cuenta activa</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" wire:click.prevent="store" class="btn btn-primary">
            {{ $cuentaId ? 'Actualizar' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      $(document).ready(function () {
        $('#cuentaModal').on('hidden.bs.modal', function () {
          if (window.Livewire) {
            Livewire.dispatch('resetInput');
          }
        });
      });
    </script>
  @endpush
</div>