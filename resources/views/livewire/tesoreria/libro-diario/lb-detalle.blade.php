<div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
          <h4 class="mb-0"><strong><i class="fas fa-list mr-2"></i>Detalles</strong></h4>
          <div class="btn-group">
            <a href="{{ route('tesoreria.libro-diario.index') }}" class="btn btn-light btn-sm">
              <i class="fas fa-arrow-left"></i> Libro Diario
            </a>
            <button type="button" class="btn btn-primary btn-sm" wire:click.prevent="create">
              <i class="fas fa-plus"></i> Nuevo Detalle
            </button>
          </div>
        </div>
        <div class="card-body px-2">
          <div class="form-row mb-3">
            <div class="col-md-8">
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" wire:model.live="search" id="search"
                  class="form-control" placeholder="Buscar por nombre...">
              </div>
            </div>
            <div class="col-md-4">
              <select class="form-control" wire:model.live="filtros.concepto_id" id="filtros_concepto_id">
                <option value="">Todos los conceptos</option>
                @foreach ($conceptos as $concepto)
                  <option value="{{ $concepto->id }}" {{ (old('filtros.concepto_id') ?? $filtros['concepto_id'] ?? null) == $concepto->id ? 'selected' : '' }}>{{ $concepto->nombre }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th class="text-center align-middle">Nombre</th>
                  <th class="text-center align-middle">Concepto</th>
                  <th class="text-center align-middle">Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($items as $item)
                  <tr>
                    <td class="text-left align-middle">{{ $item->nombre }}</td>
                    <td class="text-left align-middle">{{ $item->concepto->nombre ?? '—' }}</td>
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
                    <td colspan="3" class="text-center">No hay detalles registrados.</td>
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
          <h5 class="modal-title">{{ $item_id ? 'Editar' : 'Crear' }} Detalle</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form>
            <div class="form-group">
              <label for="concepto_id">Concepto *</label>
              <select class="form-control @error('concepto_id') is-invalid @enderror"
                wire:model="concepto_id" id="concepto_id" required>
                <option value="">Seleccione un concepto...</option>
                @foreach ($conceptos as $concepto)
                  <option value="{{ $concepto->id }}">{{ $concepto->nombre }}</option>
                @endforeach
              </select>
              @error('concepto_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
              <label for="nombre">Nombre *</label>
              <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                wire:model.live="nombre" id="nombre" list="nombreSuggestions" required autocomplete="off">
              <datalist id="nombreSuggestions">
                @foreach ($suggestedNames as $suggested)
                  <option value="{{ $suggested }}">
                @endforeach
              </datalist>
              @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            @if (!$item_id && count($opcionesAdicionales) > 0)
              <div class="form-group">
                <label class="mb-1">Opciones adicionales</label>
                <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                  <div class="custom-control custom-checkbox mb-1 pb-1" style="border-bottom: 1px solid #dee2e6;">
                    <input type="checkbox" class="custom-control-input" id="adicional_todas"
                      wire:click="seleccionarTodasAdicionales($event.target.checked)"
                      {{ collect($adicionalesSeleccionados)->every(fn($v) => $v) && count($adicionalesSeleccionados) > 0 ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold small" for="adicional_todas">TODAS</label>
                  </div>
                  @foreach ($opcionesAdicionales as $idx => $template)
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" class="custom-control-input" id="adicional_{{ $idx }}"
                        wire:model.live="adicionalesSeleccionados.{{ $idx }}">
                      <label class="custom-control-label small" for="adicional_{{ $idx }}">
                        {!! str_replace('{detalle}', '<strong>' . e($nombre ?: '{detalle}') . '</strong>', $template) !!}
                      </label>
                    </div>
                  @endforeach
                </div>
                <small class="form-text text-muted">Marque las variantes adicionales que desea crear automáticamente.</small>
              </div>
            @endif
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
          <h5 class="modal-title">Detalles del Registro</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="resetDetails()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          @if ($selectedItem)
            <p class="mb-0"><strong>Nombre:</strong> {{ $selectedItem->nombre }}</p>
            <p class="mb-0"><strong>Concepto:</strong> {{ $selectedItem->concepto->nombre ?? '—' }}</p>
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