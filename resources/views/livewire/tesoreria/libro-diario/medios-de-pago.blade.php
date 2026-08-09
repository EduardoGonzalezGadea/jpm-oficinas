<div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><strong><i class="fas fa-credit-card mr-2"></i>Medios de Pago</strong></h4>
          <div class="btn-group">
            <a href="{{ route('tesoreria.libro-diario.index') }}" class="btn btn-light btn-sm">
              <i class="fas fa-arrow-left"></i> Libro Diario
            </a>
            <button type="button" class="btn btn-primary btn-sm" wire:click.prevent="create">
              <i class="fas fa-plus"></i> Nuevo Medio
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
                <input type="text" wire:model.live="search" id="search"
                  class="form-control" placeholder="Buscar por nombre...">
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th class="text-center align-middle">Nombre</th>
                  <th class="text-center align-middle">Nombre Corto</th>
                  <th class="text-center align-middle">Activo</th>
                  <th class="text-center align-middle">Contado</th>
                  <th class="text-center align-middle">Orden</th>
                  <th class="text-center align-middle">Ámbito</th>
                  <th class="text-center align-middle">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($items as $item)
                  <tr>
                    <td class="text-left align-middle">{{ $item->nombre }}</td>
                    <td class="text-left align-middle">{{ $item->nombre_corto }}</td>
                    <td class="text-center align-middle">
                      @if ($item->activo)
                        <span class="badge badge-success">Sí</span>
                      @else
                        <span class="badge badge-secondary">No</span>
                      @endif
                    </td>
                    <td class="text-center align-middle">
                      @if ($item->contado)
                        <span class="badge badge-info">Sí</span>
                      @else
                        <span class="badge badge-light">No</span>
                      @endif
                    </td>
                    <td class="text-center align-middle">{{ $item->orden }}</td>
                    <td class="text-center align-middle">
                      @if ($item->es_libro_diario && $item->es_recaudacion)
                        <span class="badge badge-primary">Ambos</span>
                      @elseif ($item->es_libro_diario)
                        <span class="badge badge-info">Libro Diario</span>
                      @elseif ($item->es_recaudacion)
                        <span class="badge badge-success">Recaudación</span>
                      @else
                        <span class="badge badge-secondary">Ninguno</span>
                      @endif
                    </td>
                    <td class="text-center align-middle">
                      <button wire:click="showDetails({{ $item->id }})"
                        class="btn btn-sm btn-info"  title="Ver"><i class="fas fa-eye"></i></button>
                      <button wire:click="edit({{ $item->id }})"
                        class="btn btn-sm btn-primary" title="Editar"><i class="fas fa-edit"></i></button>
                      <button
                        onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $item->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                        class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center">No hay medios registrados.</td>
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
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $item_id ? 'Editar' : 'Crear' }} Medio de Pago</h5>
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
                @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-6">
                <label for="nombre_corto">Nombre Corto *</label>
                <input type="text" class="form-control @error('nombre_corto') is-invalid @enderror"
                  wire:model="nombre_corto" id="nombre_corto" required>
                @error('nombre_corto') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="form-group">
              <label for="descripcion">Descripción</label>
              <input type="text" class="form-control @error('descripcion') is-invalid @enderror"
                wire:model="descripcion" id="descripcion">
              @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="codigo_soniar">Código SONIAR</label>
                <input type="text" class="form-control @error('codigo_soniar') is-invalid @enderror"
                  wire:model="codigo_soniar" id="codigo_soniar">
                @error('codigo_soniar') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-2">
                <label for="orden">Orden</label>
                <input type="number" class="form-control @error('orden') is-invalid @enderror"
                  wire:model="orden" id="orden" min="0">
                @error('orden') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="form-group col-md-2">
                <div class="custom-control custom-checkbox mt-4 pt-2">
                  <input type="checkbox" class="custom-control-input" id="activo"
                    wire:model="activo" value="1">
                  <label class="custom-control-label" for="activo">Activo</label>
                </div>
              </div>
              <div class="form-group col-md-2">
                <div class="custom-control custom-checkbox mt-4 pt-2">
                  <input type="checkbox" class="custom-control-input" id="contado"
                    wire:model="contado" value="1">
                  <label class="custom-control-label" for="contado">Contado</label>
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="es_libro_diario"
                    wire:model="es_libro_diario" value="1">
                  <label class="custom-control-label" for="es_libro_diario">Disponible en Libro Diario</label>
                </div>
              </div>
              <div class="form-group col-md-4">
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="es_recaudacion"
                    wire:model="es_recaudacion" value="1">
                  <label class="custom-control-label" for="es_recaudacion">Disponible en Recaudación</label>
                </div>
              </div>
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
          <h5 class="modal-title">Detalles del Medio de Pago</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetDetails()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          @if ($selectedItem)
            <p class="mb-0"><strong>Nombre:</strong> {{ $selectedItem->nombre }}</p>
            <p class="mb-0"><strong>Nombre Corto:</strong> {{ $selectedItem->nombre_corto }}</p>
            <p class="mb-0"><strong>Descripción:</strong> {{ $selectedItem->descripcion ?? '-' }}</p>
            <p class="mb-0"><strong>Código SONIAR:</strong> {{ $selectedItem->codigo_soniar ?? '-' }}</p>
            <p class="mb-0"><strong>Activo:</strong> {{ $selectedItem->activo ? 'Sí' : 'No' }}</p>
            <p class="mb-0"><strong>Contado:</strong> {{ $selectedItem->contado ? 'Sí' : 'No' }}</p>
            <p class="mb-0"><strong>Ámbito:</strong>
              @if ($selectedItem->es_libro_diario && $selectedItem->es_recaudacion)
                Libro Diario + Recaudación
              @elseif ($selectedItem->es_libro_diario)
                Solo Libro Diario
              @elseif ($selectedItem->es_recaudacion)
                Solo Recaudación
              @else
                Ninguno
              @endif
            </p>
            <p class="mb-0"><strong>Orden:</strong> {{ $selectedItem->orden }}</p>
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

      window.addEventListener('alert', event => {
        const d = window.LiveEvent(event);
        Swal.fire({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          icon: d.type,
          title: d.message,
        });
      });

      document.addEventListener('livewire:init', function() {
      Livewire.on('itemStore', () => $('#modal').modal('hide'));
      Livewire.on('itemUpdate', () => $('#modal').modal('hide'));
      });

      $(document).ready(function() {
        $('#modal').on('hidden.bs.modal', function() {
          window.Livewire.dispatch('resetForm');
        });
      });
    </script>
  @endpush
</div>