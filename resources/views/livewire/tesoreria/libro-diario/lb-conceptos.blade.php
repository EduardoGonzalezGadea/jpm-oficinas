<div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><strong><i class="fas fa-folder-open mr-2"></i>Conceptos</strong></h4>
          <div class="btn-group">
            <a href="{{ route('tesoreria.libro-diario.index') }}" class="btn btn-light btn-sm">
              <i class="fas fa-arrow-left"></i> Libro Diario
            </a>
            <button type="button" class="btn btn-primary btn-sm" wire:click.prevent="create">
              <i class="fas fa-plus"></i> Nuevo Concepto
            </button>
          </div>
        </div>
        <div class="card-body px-2">
          <div class="form-row mb-3">
            <div class="col-md-12">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" wire:model="search" id="search"
                  class="form-control" placeholder="Buscar por nombre...">
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th class="text-center align-middle">Nombre</th>
                  <th class="text-center align-middle">Detalles asociados</th>
                  <th class="text-center align-middle">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($items as $item)
                  <tr>
                    <td class="text-left align-middle">{{ $item->nombre }}</td>
                    <td class="text-center align-middle">{{ $item->detalles_count }}</td>
                    <td class="text-center align-middle">
                      <button wire:click="showDetails({{ $item->id }})"
                        class="btn btn-sm btn-info" data-toggle="modal"
                        data-target="#detailsModal" title="Ver"><i class="fas fa-eye"></i></button>
                      <button wire:click="edit({{ $item->id }})"
                        class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></button>
                      <button
                        onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $item->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                        class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center">No hay conceptos registrados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-center">
            {{ $items->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <div wire:ignore.self class="modal fade" id="modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $item_id ? 'Editar' : 'Crear' }} Concepto</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form>
            <div class="form-group">
              <label for="nombre">Nombre *</label>
              <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                wire:model.defer="nombre" id="nombre" required>
              @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="button" wire:click.prevent="{{ $item_id ? 'update()' : 'store()' }}"
            class="btn btn-primary">{{ $item_id ? 'Actualizar' : 'Guardar' }}</button>
        </div>
      </div>
    </div>
  </div>

  <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalles del Concepto</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetDetails()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          @if ($selectedItem)
            <p class="mb-0"><strong>Nombre:</strong> {{ $selectedItem->nombre }}</p>
            <p class="mb-0"><strong>Detalles asociados:</strong></p>
            <ul>
              @forelse($selectedItem->detalles as $detalle)
                <li>{{ $detalle->nombre }}</li>
              @empty
                <li class="text-muted">Sin detalles</li>
              @endforelse
            </ul>
            <p class="mb-0"><strong>Fecha de Creación:</strong> {{ $selectedItem->created_at?->format('d/m/Y H:i') }}</p>
            <p class="mb-0"><strong>Última Actualización:</strong> {{ $selectedItem->updated_at?->format('d/m/Y H:i') }}</p>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="resetDetails()">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      window.addEventListener('swal:confirm', event => {
        Swal.fire({
          title: event.detail.title,
          text: event.detail.text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: event.detail.confirmButtonText,
          cancelButtonText: 'Cancelar',
          focusConfirm: true
        }).then((result) => {
          if (result.isConfirmed) {
            @this.call(event.detail.method, event.detail.id);
          }
        });
      });

      window.addEventListener('alert', event => {
        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: event.detail.type,
          title: event.detail.message,
        });
      });

      window.livewire.on('itemStore', () => $('#modal').modal('hide'));
      window.livewire.on('itemUpdate', () => $('#modal').modal('hide'));

      $(document).ready(function() {
        $('#modal').on('hidden.bs.modal', function() {
          window.livewire.emit('resetForm');
        });
      });
    </script>
  @endpush
</div>
