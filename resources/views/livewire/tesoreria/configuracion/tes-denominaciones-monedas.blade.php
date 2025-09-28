<div>
    <style>
        .text-nowrap-custom {
            white-space: nowrap;
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Denominaciones de Moneda</h3>
                    <button type="button" class="btn btn-primary" wire:click.prevent="create">
                        <i class="fas fa-plus"></i> Nueva Denominación
                    </button>
                </div>
                <div class="card-body">
                    <!-- Selector de búsqueda -->
                    <div class="form-row mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" wire:model.live="search" id="search"
                                    class="form-control"
                                    placeholder="Buscar por tipo, denominación o descripción...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Tipo</th>
                                    <th class="text-center align-middle">Denominación</th>
                                    <th class="text-center align-middle">Descripción</th>
                                    <th class="text-center align-middle">Estado</th>
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($denominaciones as $denominacion)
                                    <tr>
                                        <td class="text-left align-middle">
                                            <span class="badge badge-info">{{ $denominacion->tipo_moneda_formateado }}</span>
                                        </td>
                                        <td class="text-right align-middle font-weight-bold">
                                            {{ $denominacion->denominacion_formateada }}
                                        </td>
                                        <td class="text-left align-middle">{{ $denominacion->descripcion ?: 'Sin descripción' }}</td>
                                        <td class="text-center align-middle">
                                            @if($denominacion->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $denominacion->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsModal" title="Ver"><i
                                                    class="fas fa-eye"></i></button>
                                            <button wire:click="edit({{ $denominacion->id }})"
                                                class="btn btn-sm btn-primary" title="Editar"><i
                                                    class="fas fa-edit"></i></button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $denominacion->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar"><i
                                                    class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay denominaciones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $denominaciones->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div wire:ignore.self class="modal fade" id="denominacionModal" tabindex="-1" role="dialog"
        aria-labelledby="denominacionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="denominacionModalLabel">{{ $denominacion_moneda_id ? 'Editar' : 'Crear' }} Denominación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="tipo_moneda">Tipo de Moneda *</label>
                            <select class="form-control @error('tipo_moneda') is-invalid @enderror"
                                wire:model.defer="tipo_moneda" id="tipo_moneda" required>
                                <option value="">Seleccionar tipo...</option>
                                @foreach($tiposMoneda as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('tipo_moneda')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="denominacion">Denominación *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" step="0.01" min="0" max="999999.99"
                                    class="form-control @error('denominacion') is-invalid @enderror"
                                    wire:model.defer="denominacion" id="denominacion"
                                    placeholder="0.00" required>
                            </div>
                            @error('denominacion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                wire:model.defer="descripcion" id="descripcion" rows="3"
                                placeholder="Descripción opcional de la denominación"></textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" wire:model.defer="activo"
                                    id="activo" value="1" {{ $activo ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activo">Activo</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click.prevent="{{ $denominacion_moneda_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">{{ $denominacion_moneda_id ? 'Actualizar' : 'Guardar' }}</button>
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
                    <h5 class="modal-title" id="detailsModalLabel">Detalles de la Denominación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedDenominacion)
                        <p class="mb-0"><strong>Tipo:</strong> {{ $selectedDenominacion->tipo_moneda_formateado }}</p>
                        <p class="mb-0"><strong>Denominación:</strong> {{ $selectedDenominacion->denominacion_formateada }}</p>
                        <p class="mb-0"><strong>Descripción:</strong> {{ $selectedDenominacion->descripcion ?: 'Sin descripción' }}</p>
                        <p class="mb-0"><strong>Estado:</strong>
                            @if($selectedDenominacion->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </p>
                        <p class="mb-0"><strong>Fecha de Creación:</strong> {{ $selectedDenominacion->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mb-0"><strong>Última Actualización:</strong> {{ $selectedDenominacion->updated_at->format('d/m/Y H:i') }}</p>
                        @if($selectedDenominacion->creator)
                            <p class="mb-0"><strong>Creado por:</strong> {{ $selectedDenominacion->creator->name }}</p>
                        @endif
                        @if($selectedDenominacion->updater)
                            <p class="mb-0"><strong>Actualizado por:</strong> {{ $selectedDenominacion->updater->name }}</p>
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

            window.addEventListener('close-modal', event => {
                $('#denominacionModal').modal('hide');
            });

            window.addEventListener('alert', event => {
                const type = event.detail.type;
                const message = event.detail.message;
                const isToast = event.detail.toast || false;

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

            window.livewire.on('denominacionStore', () => {
                $('#denominacionModal').modal('hide');
            });

            window.livewire.on('denominacionUpdate', () => {
                $('#denominacionModal').modal('hide');
            });

            $(document).ready(function() {
                $('#denominacionModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });
            });
        </script>
    @endpush
</div>
