<div>
  <div class="card">
    <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><strong><i class="fas fa-university mr-2"></i>Bancos</strong></h4>
      <button wire:click="create" type="button" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Nuevo Banco
      </button>
    </div>
    <div class="card-body px-2">
      <div class="input-group mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
        <input type="text" wire:model.live.debounce.400ms="search"
               class="form-control" placeholder="Buscar por nombre o código...">
      </div>

      @if($bancos->count() > 0)
        <div class="table-responsive">
          <table class="table table-bordered table-sm">
            <thead>
              <tr>
                <th class="align-middle">Nombre</th>
                <th class="align-middle">Código</th>
                <th class="align-middle">Observaciones</th>
                <th class="text-center align-middle">Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($bancos as $banco)
                <tr wire:key="banco-row-{{ $banco->id }}">
                  <td class="align-middle">{{ $banco->nombre }}</td>
                  <td class="align-middle">{{ $banco->codigo }}</td>
                  <td class="align-middle">{{ $banco->observaciones ?? '—' }}</td>
                  <td class="text-center align-middle">
                    <button wire:click="edit({{ $banco->id }})"
                            class="btn btn-sm btn-primary" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: 'Se eliminará el banco.', method: 'destroy', id: {{ $banco->id }}, confirmButtonText: 'Sí, eliminar' } }))"
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
          {{ $bancos->links() }}
        </div>
      @else
        <div class="alert alert-info">
          <i class="fas fa-info-circle mr-1"></i>
          No hay bancos registrados aún.
        </div>
      @endif
    </div>
  </div>

  <!-- Modal Banco -->
  <div wire:ignore.self wire:key="banco-modal" class="modal fade" id="bancoModal"
       tabindex="-1" role="dialog" aria-labelledby="bancoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bancoModalLabel">
            {{ $bancoId ? 'Editar Banco' : 'Nuevo Banco' }}
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="banco_nombre">Nombre *</label>
            <input type="text"
                   class="form-control @error('nombre') is-invalid @enderror"
                   wire:model="nombre" id="banco_nombre">
            @error('nombre')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group">
            <label for="banco_codigo">Código *</label>
            <input type="text"
                   class="form-control @error('codigo') is-invalid @enderror"
                   wire:model="codigo" id="banco_codigo">
            @error('codigo')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group">
            <label for="banco_observaciones">Observaciones</label>
            <textarea class="form-control" wire:model="observaciones"
                      id="banco_observaciones" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="button" wire:click.prevent="store" class="btn btn-primary">
            {{ $bancoId ? 'Actualizar' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      window.addEventListener('swal:confirm', event => {
        const d = event.detail;
        Swal.fire({
          title: d.title,
          text: d.text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: d.confirmButtonText,
          cancelButtonText: 'Cancelar',
        }).then(result => {
          if (result.isConfirmed) {
            @this.call(d.method, d.id);
          }
        });
      });

      window.addEventListener('close-modal', () => {
        $('#bancoModal').modal('hide');
      });

      $(document).ready(function () {
        $('#bancoModal').on('hidden.bs.modal', function () {
          @this.call('resetInput');
        });
      });
    </script>
  @endpush
</div>