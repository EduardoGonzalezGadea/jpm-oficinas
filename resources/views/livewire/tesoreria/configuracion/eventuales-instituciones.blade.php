<div class="eventuales-instituciones-root">
  <div>
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-building mr-2"></i>Instituciones de Eventuales</strong></h4>
            <button type="button" class="btn btn-primary" wire:click.prevent="create">
              <i class="fas fa-plus"></i> Nueva Institución
            </button>
          </div>
          <div class="card-body px-2">
            {{-- Buscador --}}
            <div class="form-row mb-3">
              <div class="col-md-12">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                  </div>
                  <input type="text" wire:model.live="search" id="search-instituciones"
                    class="form-control"
                    placeholder="Buscar por nombre o descripción...">
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead>
                  <tr>
                    <th class="text-center align-middle">Nombre</th>
                    <th class="text-center align-middle">Descripción</th>
                    <th class="text-center align-middle">Estado</th>
                    <th class="text-center align-middle">Eventuales</th>
                    <th class="text-center align-middle">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($instituciones as $institucion)
                    <tr wire:key="institucion-{{ $institucion->id }}">
                      <td class="align-middle">
                        <strong>{{ $institucion->nombre }}</strong>
                      </td>
                      <td class="align-middle">
                        {{ $institucion->descripcion ?? '—' }}
                      </td>
                      <td class="text-center align-middle">
                        <div class="custom-control custom-switch d-inline-block">
                          <input type="checkbox" class="custom-control-input"
                            wire:click="toggleActiva({{ $institucion->id }})"
                            id="activa-switch-{{ $institucion->id }}"
                            @if($institucion->activa) checked @endif>
                          <label class="custom-control-label" for="activa-switch-{{ $institucion->id }}">
                            <span class="badge badge-{{ $institucion->activa ? 'success' : 'secondary' }}">
                              {{ $institucion->activa ? 'Activa' : 'Inactiva' }}
                            </span>
                          </label>
                        </div>
                      </td>
                      <td class="text-center align-middle">
                        <span class="badge badge-info">
                          {{ $institucion->eventuales()->count() }}
                        </span>
                      </td>
                      <td class="text-center align-middle">
                        <button wire:click="showDetails({{ $institucion->id }})"
                          class="btn btn-sm btn-info" data-toggle="modal"
                          data-target="#detailsModal" title="Ver detalles">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button wire:click="edit({{ $institucion->id }})"
                          class="btn btn-sm btn-primary" title="Editar">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡Se eliminará la institución!', method: 'destroy', id: {{ $institucion->id }}, confirmButtonText: 'Sí, elimínala' } }))"
                          class="btn btn-sm btn-danger" title="Eliminar">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center">No hay instituciones registradas.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-center">
              {{ $instituciones->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal Crear / Editar --}}
    <div wire:ignore.self class="modal fade" id="institucionModal" tabindex="-1" role="dialog"
      aria-labelledby="institucionModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="institucionModalLabel">
              {{ $institucion_id ? 'Editar' : 'Nueva' }} Institución
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form>
              <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text"
                  class="form-control @error('nombre') is-invalid @enderror"
                  wire:model="nombre"
                  id="nombre"
                  placeholder="Ej: INTENDENCIA MUNICIPAL"
                  required>
                @error('nombre')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea
                  class="form-control @error('descripcion') is-invalid @enderror"
                  wire:model="descripcion"
                  id="descripcion"
                  rows="3"
                  placeholder="Descripción opcional de la institución..."></textarea>
                @error('descripcion')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input"
                    wire:model="activa"
                    id="activa">
                  <label class="custom-control-label" for="activa">
                    Institución activa
                  </label>
                  <small class="form-text text-muted">Las instituciones inactivas no estarán disponibles para nuevos eventuales.</small>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            <button type="button"
              wire:click.prevent="{{ $institucion_id ? 'update()' : 'store()' }}"
              class="btn btn-primary">
              {{ $institucion_id ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal Detalles --}}
    <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog"
      aria-labelledby="detailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailsModalLabel">Detalles de la Institución</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
              wire:click="resetDetails()">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            @if ($selectedInstitucion)
              <div class="row">
                <div class="col-md-6">
                  <p class="mb-2"><strong>Nombre:</strong> {{ $selectedInstitucion->nombre }}</p>
                  <p class="mb-2"><strong>Descripción:</strong> {{ $selectedInstitucion->descripcion ?? 'Sin descripción' }}</p>
                  <p class="mb-2"><strong>Estado:</strong> 
                    <span class="badge badge-{{ $selectedInstitucion->activa ? 'success' : 'secondary' }}">
                      {{ $selectedInstitucion->activa ? 'Activa' : 'Inactiva' }}
                    </span>
                  </p>
                </div>
                <div class="col-md-6">
                  <p class="mb-2"><strong>Total de Eventuales:</strong> 
                    <span class="badge badge-info">{{ $selectedInstitucion->eventuales->count() }}</span>
                  </p>
                  @if($selectedInstitucion->eventuales->count() > 0)
                    <p class="mb-2"><strong>Monto Total:</strong> 
                      $ {{ number_format($selectedInstitucion->eventuales->sum('monto'), 2, ',', '.') }}
                    </p>
                  @endif
                </div>
              </div>
              <hr>
              <p class="mb-1 text-muted small"><strong>Creada:</strong> {{ $selectedInstitucion->created_at?->format('d/m/Y H:i') }}</p>
              <p class="mb-0 text-muted small"><strong>Última actualización:</strong> {{ $selectedInstitucion->updated_at?->format('d/m/Y H:i') }}</p>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal"
              wire:click="resetDetails()">Cerrar</button>
          </div>
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

      window.addEventListener('show-modal', event => {
        const d = window.LiveEvent(event);
        if (d.modal) {
          $(d.modal).modal('show');
        } else if (d.id) {
          $('#' + d.id).modal('show');
        }
      });

      window.addEventListener('close-modal', event => {
        const d = window.LiveEvent(event);
        $(d.modal).modal('hide');
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

      $(document).ready(function() {
        $('#institucionModal').on('hidden.bs.modal', function() {
          window.Livewire.dispatch('resetForm');
        });

        $('#institucionModal').on('shown.bs.modal', function() {
          $('#nombre').focus();
        });
      });
    </script>
  @endpush
</div>
