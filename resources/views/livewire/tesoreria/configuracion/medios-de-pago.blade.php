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
          <h4 class="mb-0"><strong><i class="fas fa-credit-card mr-2"></i>Medios de Pago</strong></h4>
          <button type="button" class="btn btn-primary" wire:click.prevent="create">
            <i class="fas fa-plus"></i> Nuevo Medio de Pago
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
                  placeholder="Buscar por nombre o descripción...">
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th class="text-center align-middle">Nombre</th>
                  <th class="text-center align-middle">Nombre Corto</th>
                  <th class="text-center align-middle">Orden</th>
                  <th class="text-center align-middle">Etiquetas</th>
                  <th class="text-center align-middle">Estado</th>
                  <th class="text-center align-middle">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($mediosDePago as $medio)
                  <tr>
                    <td class="text-left align-middle">{{ $medio->nombre }}</td>
                    <td class="text-center align-middle">{{ $medio->nombre_corto ?: '—' }}</td>
                    <td class="text-center align-middle">{{ $medio->orden }}</td>
                    <td class="text-center align-middle">
                      @if($medio->es_libro_diario)<span class="badge badge-info mr-1" title="Libro Diario">LD</span>@endif
                      @if($medio->es_recaudacion)<span class="badge badge-warning mr-1" title="Recaudación">REC</span>@endif
                      @if($medio->contado)<span class="badge badge-success mr-1" title="Contado (Efectivo)">CONT</span>@endif
                      @if(!$medio->es_libro_diario && !$medio->es_recaudacion && !$medio->contado)
                        <span class="text-muted">—</span>
                      @endif
                    </td>
                    <td class="text-center align-middle">
                      @if($medio->activo)
                        <span class="badge badge-success">Activo</span>
                      @else
                        <span class="badge badge-secondary">Inactivo</span>
                      @endif
                    </td>
                    <td class="text-center align-middle">
                      <button wire:click="showDetails({{ $medio->id }})"
                        class="btn btn-sm btn-info"  title="Ver"><i
                          class="fas fa-eye"></i></button>
                      <button wire:click="edit({{ $medio->id }})"
                        class="btn btn-sm btn-primary" title="Editar"><i
                          class="fas fa-edit"></i></button>
                      <button
                        onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $medio->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                        class="btn btn-sm btn-danger" title="Eliminar"><i
                          class="fas fa-trash-alt"></i></button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center">No hay medios de pago registrados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-center">
            {{ $mediosDePago->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create/Edit Modal -->
  <div wire:ignore.self class="modal fade" id="medioDePagoModal" tabindex="-1" role="dialog"
    aria-labelledby="medioDePagoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="medioDePagoModalLabel">{{ $medio_de_pago_id ? 'Editar' : 'Crear' }} Medio de Pago</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="nombre">Nombre *</label>
                <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                  wire:model="nombre" id="nombre" required>
                @error('nombre')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group col-md-6">
                <label for="nombre_corto">Nombre Corto</label>
                <input type="text" class="form-control @error('nombre_corto') is-invalid @enderror"
                  wire:model="nombre_corto" id="nombre_corto" placeholder="Ej: TRANSFERENCIA">
                @error('nombre_corto')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-group">
              <label for="descripcion">Descripción</label>
              <textarea class="form-control @error('descripcion') is-invalid @enderror"
                wire:model="descripcion" id="descripcion" rows="2"></textarea>
              @error('descripcion')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="orden">Orden</label>
                <input type="number" class="form-control @error('orden') is-invalid @enderror"
                  wire:model="orden" id="orden" min="0">
                @error('orden')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group col-md-6">
                <label for="codigo_soniar">Código SONIAR</label>
                <input type="text" class="form-control @error('codigo_soniar') is-invalid @enderror"
                  wire:model="codigo_soniar" id="codigo_soniar" placeholder="Opcional">
                @error('codigo_soniar')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" wire:model="activo"
                    id="activo" value="1">
                  <label class="custom-control-label" for="activo">Activo</label>
                </div>
              </div>

              <div class="form-group col-md-4">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" wire:model="es_libro_diario"
                    id="es_libro_diario" value="1">
                  <label class="custom-control-label" for="es_libro_diario">Libro Diario</label>
                </div>
              </div>

              <div class="form-group col-md-4">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" wire:model="es_recaudacion"
                    id="es_recaudacion" value="1">
                  <label class="custom-control-label" for="es_recaudacion">Recaudación</label>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" wire:model="contado"
                  id="contado" value="1">
                <label class="custom-control-label" for="contado">Contado (Efectivo)</label>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="button" wire:click.prevent="{{ $medio_de_pago_id ? 'update()' : 'store()' }}"
            class="btn btn-primary">{{ $medio_de_pago_id ? 'Actualizar' : 'Guardar' }}</button>
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
          <h5 class="modal-title" id="detailsModalLabel">Detalles del Medio de Pago</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"
            wire:click="resetDetails()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          @if ($selectedMedioDePago)
            <p class="mb-0"><strong>Nombre:</strong> {{ $selectedMedioDePago->nombre }}</p>
            <p class="mb-0"><strong>Nombre Corto:</strong> {{ $selectedMedioDePago->nombre_corto ?: '—' }}</p>
            <p class="mb-0"><strong>Descripción:</strong> {{ $selectedMedioDePago->descripcion ?: 'Sin descripción' }}</p>
            <p class="mb-0"><strong>Orden:</strong> {{ $selectedMedioDePago->orden }}</p>
            <p class="mb-0"><strong>Código SONIAR:</strong> {{ $selectedMedioDePago->codigo_soniar ?: '—' }}</p>
            <p class="mb-0"><strong>Etiquetas:</strong>
              @if($selectedMedioDePago->es_libro_diario)<span class="badge badge-info mr-1">Libro Diario</span>@endif
              @if($selectedMedioDePago->es_recaudacion)<span class="badge badge-warning mr-1">Recaudación</span>@endif
              @if($selectedMedioDePago->contado)<span class="badge badge-success mr-1">Contado</span>@endif
              @if(!$selectedMedioDePago->es_libro_diario && !$selectedMedioDePago->es_recaudacion && !$selectedMedioDePago->contado)
                <span class="text-muted">Ninguna</span>
              @endif
            </p>
            <p class="mb-0"><strong>Estado:</strong>
              @if($selectedMedioDePago->activo)
                <span class="badge badge-success">Activo</span>
              @else
                <span class="badge badge-secondary">Inactivo</span>
              @endif
            </p>
            <p class="mb-0"><strong>Fecha de Creación:</strong> {{ $selectedMedioDePago->created_at->format('d/m/Y H:i') }}</p>
            <p class="mb-0"><strong>Última Actualización:</strong> {{ $selectedMedioDePago->updated_at->format('d/m/Y H:i') }}</p>
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
        $('#medioDePagoModal').modal('hide');
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
      Livewire.on('medioDePagoStore', () => {
        $('#medioDePagoModal').modal('hide');
      });

      Livewire.on('medioDePagoUpdate', () => {
        $('#medioDePagoModal').modal('hide');
      });
      });

      $(document).ready(function() {
        $('#medioDePagoModal').on('hidden.bs.modal', function() {
          window.Livewire.dispatch('resetForm');
        });
      });
    </script>
  @endpush
</div>