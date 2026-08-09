<div>
  <style>
    .text-nowrap-custom {
      white-space: nowrap;
    }
  </style>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><strong><i class="fas fa-money-bill-wave mr-2"></i>Discriminación Monetaria</strong></h4>
          <button type="button" class="btn btn-primary" wire:click.prevent="create">
            <i class="fas fa-plus"></i> Nueva Discriminación
          </button>
        </div>
        <div class="card-body px-2">
          <!-- Selector de búsqueda -->
          <div class="form-row mb-3">
            <div class="col-md-12">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" wire:model.live="search" id="search"
                  class="form-control"
                  placeholder="Buscar por tipo, valor o texto...">
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th class="text-center align-middle">Tipo</th>
                  <th class="text-center align-middle">Valor</th>
                  <th class="text-center align-middle">Texto</th>
                  <th class="text-center align-middle">Estado</th>
                  <th class="text-center align-middle">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($discriminaciones as $disc)
                  <tr>
                    <td class="text-left align-middle">
                      @if($disc->tipo == 'Billetes')
                        <i class="fas fa-money-bill text-success mr-1"></i>
                      @else
                        <i class="fas fa-coins text-warning mr-1"></i>
                      @endif
                      {{ $disc->tipo }}
                    </td>
                    <td class="text-right align-middle">
                      <strong>$ {{ number_format($disc->valor, 2, ',', '.') }}</strong>
                    </td>
                    <td class="text-left align-middle">{{ $disc->texto }}</td>
                    <td class="text-center align-middle">
                      @if($disc->activo)
                        <span class="badge badge-success">Activo</span>
                      @else
                        <span class="badge badge-secondary">Inactivo</span>
                      @endif
                    </td>
                    <td class="text-center align-middle">
                      <button wire:click="showDetails({{ $disc->id }})"
                        class="btn btn-sm btn-info"  title="Ver"><i
                          class="fas fa-eye"></i></button>
                      <button wire:click="edit({{ $disc->id }})"
                        class="btn btn-sm btn-primary" title="Editar"><i
                          class="fas fa-edit"></i></button>
                      <button
                        onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $disc->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                        class="btn btn-sm btn-danger" title="Eliminar"><i
                          class="fas fa-trash-alt"></i></button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center">No hay discriminaciones monetarias registradas.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-center">
            {{ $discriminaciones->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <div wire:ignore.self class="modal fade" id="discriminacionModal" tabindex="-1" role="dialog"
    aria-labelledby="discriminacionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="discriminacionModalLabel">{{ $discriminacion_monetaria_id ? 'Editar' : 'Crear' }} Discriminación Monetaria</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form>
            <div class="form-group">
              <label for="tipo">Tipo *</label>
              <select class="form-control @error('tipo') is-invalid @enderror"
                wire:model="tipo" id="tipo" required>
                <option value="">Seleccione un tipo</option>
                <option value="Billetes">Billetes</option>
                <option value="Monedas">Monedas</option>
              </select>
              @error('tipo')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="valor">Valor *</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">$</span>
                </div>
                <input type="number" step="0.01" min="0" max="999999.99"
                  class="form-control @error('valor') is-invalid @enderror"
                  wire:model="valor" id="valor" required
                  placeholder="Ej: 2000">
                @error('valor')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <small class="form-text">Ingrese el valor numérico (ej: 2000, 1000, 500)</small>
            </div>

            <div class="form-group">
              <label for="texto">Texto *</label>
              <input type="text" class="form-control @error('texto') is-invalid @enderror"
                wire:model="texto" id="texto" required
                placeholder="Ej: dos mil"
                maxlength="100">
              @error('texto')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text">Texto descriptivo del valor (ej: dos mil, mil, quinientos)</small>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" wire:model="activo"
                  id="activo" value="1" {{ $activo ? 'checked' : '' }}>
                <label class="custom-control-label" for="activo">Activo</label>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="button" wire:click.prevent="{{ $discriminacion_monetaria_id ? 'update()' : 'store()' }}"
            class="btn btn-primary">{{ $discriminacion_monetaria_id ? 'Actualizar' : 'Guardar' }}</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Details Modal -->
  <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog"
    aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detailsModalLabel">Detalles de la Discriminación Monetaria</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"
            wire:click="resetDetails()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          @if ($selectedDiscriminacion)
            <p class="mb-2">
              <strong>Tipo:</strong>
              @if($selectedDiscriminacion->tipo == 'Billetes')
                <i class="fas fa-money-bill text-success mr-1"></i>
              @else
                <i class="fas fa-coins text-warning mr-1"></i>
              @endif
              {{ $selectedDiscriminacion->tipo }}
            </p>
            <p class="mb-2"><strong>Valor:</strong> $ {{ number_format($selectedDiscriminacion->valor, 2, ',', '.') }}</p>
            <p class="mb-2"><strong>Texto:</strong> {{ $selectedDiscriminacion->texto }}</p>
            <p class="mb-2"><strong>Descripción completa:</strong> {{ $selectedDiscriminacion->descripcion_completa }}</p>
            <p class="mb-2"><strong>Estado:</strong>
              @if($selectedDiscriminacion->activo)
                <span class="badge badge-success">Activo</span>
              @else
                <span class="badge badge-secondary">Inactivo</span>
              @endif
            </p>
            <hr>
            <p class="mb-2"><strong>Fecha de Creación:</strong> {{ $selectedDiscriminacion->created_at->format('d/m/Y H:i') }}</p>
            <p class="mb-2"><strong>Última Actualización:</strong> {{ $selectedDiscriminacion->updated_at->format('d/m/Y H:i') }}</p>
            @if($selectedDiscriminacion->creator)
              <p class="mb-2"><strong>Creado por:</strong> {{ $selectedDiscriminacion->creator->name }}</p>
            @endif
            @if($selectedDiscriminacion->updater)
              <p class="mb-0"><strong>Actualizado por:</strong> {{ $selectedDiscriminacion->updater->name }}</p>
            @endif
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"
            wire:click="resetDetails()">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      window.addEventListener('swal:confirm', event => {
        const d = window.LiveEvent(event);
        Swal.fire({
          title: d.title,
          text: d.text,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: d.confirmButtonText,
          cancelButtonText: 'Cancelar',
          focusConfirm: true
        }).then((result) => {
          if (result.isConfirmed) {
            @this.call(d.method, d.id);
          }
        });
      });

      window.addEventListener('close-modal', event => {
        $('#discriminacionModal').modal('hide');
      });

      window.addEventListener('alert', event => {
        const d = window.LiveEvent(event);
        const type = d.type;
        const message = d.message;
        const isToast = d.toast || false;

        if (isToast) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: type,
            title: message,
          });
        } else {
          Swal.fire({
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 1500
          });
        }
      });

      document.addEventListener('livewire:init', function() {
      Livewire.on('discriminacionStore', () => {
        $('#discriminacionModal').modal('hide');
      });

      Livewire.on('discriminacionUpdate', () => {
        $('#discriminacionModal').modal('hide');
      });
      });

      $(document).ready(function() {
        $('#discriminacionModal').on('hidden.bs.modal', function() {
          window.Livewire.dispatch('resetForm');
        });
      });
    </script>
  @endpush
</div>