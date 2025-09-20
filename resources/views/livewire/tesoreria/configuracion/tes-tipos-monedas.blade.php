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
                    <h3 class="mb-0">Tipos de Monedas</h3>
                    <button type="button" class="btn btn-primary" wire:click.prevent="create">
                        <i class="fas fa-plus"></i> Nuevo Tipo de Moneda
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
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tiposMonedas as $tipo)
                                    <tr>
                                        <td class="text-left align-middle">{{ $tipo->nombre }}</td>
                                        <td class="text-left align-middle">{{ $tipo->descripcion ?: 'Sin descripción' }}</td>
                                        <td class="text-center align-middle">
                                            @if($tipo->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $tipo->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsModal" title="Ver"><i
                                                    class="fas fa-eye"></i></button>
                                            <button wire:click="edit({{ $tipo->id }})"
                                                class="btn btn-sm btn-primary" title="Editar"><i
                                                    class="fas fa-edit"></i></button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $tipo->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar"><i
                                                    class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No hay tipos de monedas registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $tiposMonedas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div wire:ignore.self class="modal fade" id="tipoMonedaModal" tabindex="-1" role="dialog"
        aria-labelledby="tipoMonedaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tipoMonedaModalLabel">{{ $tipo_moneda_id ? 'Editar' : 'Crear' }} Tipo de Moneda</h5>
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
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                wire:model.defer="descripcion" id="descripcion" rows="3"></textarea>
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
                    <button type="button" wire:click.prevent="{{ $tipo_moneda_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">{{ $tipo_moneda_id ? 'Actualizar' : 'Guardar' }}</button>
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
                    <h5 class="modal-title" id="detailsModalLabel">Detalles del Tipo de Moneda</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedTipoMoneda)
                        <p class="mb-0"><strong>Nombre:</strong> {{ $selectedTipoMoneda->nombre }}</p>
                        <p class="mb-0"><strong>Descripción:</strong> {{ $selectedTipoMoneda->descripcion ?: 'Sin descripción' }}</p>
                        <p class="mb-0"><strong>Estado:</strong>
                            @if($selectedTipoMoneda->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-secondary">Inactivo</span>
                            @endif
                        </p>
                        <p class="mb-0"><strong>Fecha de Creación:</strong> {{ $selectedTipoMoneda->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mb-0"><strong>Última Actualización:</strong> {{ $selectedTipoMoneda->updated_at->format('d/m/Y H:i') }}</p>
                        @if($selectedTipoMoneda->creator)
                            <p class="mb-0"><strong>Creado por:</strong> {{ $selectedTipoMoneda->creator->name }}</p>
                        @endif
                        @if($selectedTipoMoneda->updater)
                            <p class="mb-0"><strong>Actualizado por:</strong> {{ $selectedTipoMoneda->updater->name }}</p>
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
                $('#tipoMonedaModal').modal('hide');
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

            window.livewire.on('tipoMonedaStore', () => {
                $('#tipoMonedaModal').modal('hide');
            });

            window.livewire.on('tipoMonedaUpdate', () => {
                $('#tipoMonedaModal').modal('hide');
            });

            $(document).ready(function() {
                $('#tipoMonedaModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });
            });
        </script>
    @endpush
</div>
