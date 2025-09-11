<div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Instituciones de Eventuales</h3>
                    <div class="btn-group">
                        <a href="{{ route('tesoreria.eventuales.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Eventuales
                        </a>
                        <button type="button" class="btn btn-primary" wire:click.prevent="create">
                            <i class="fas fa-plus"></i> Nueva Institución
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="search">Buscar</label>
                            <input type="text" wire:model="search" id="search"
                                class="form-control form-control-sm"
                                placeholder="Buscar por nombre o descripción...">
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
                                    <tr>
                                        <td class="text-left align-middle">
                                            <strong>{{ $institucion->nombre }}</strong>
                                        </td>
                                        <td class="text-left align-middle">
                                            {{ $institucion->descripcion ?? 'Sin descripción' }}
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-{{ $institucion->activa ? 'success' : 'secondary' }}">
                                                {{ $institucion->activa ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-info">
                                                {{ $institucion->eventuales()->count() }}
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $institucion->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsModal" title="Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button wire:click="edit({{ $institucion->id }})"
                                                class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="toggleActiva({{ $institucion->id }})"
                                                class="btn btn-sm btn-{{ $institucion->activa ? 'warning' : 'success' }}" 
                                                title="{{ $institucion->activa ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas fa-{{ $institucion->activa ? 'eye-slash' : 'eye' }}"></i>
                                            </button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $institucion->id }}, confirmButtonText: 'Sí, elimínala' } }))"
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

    <!-- Create/Edit Modal -->
    <div wire:ignore.self class="modal fade" id="institucionModal" tabindex="-1" role="dialog"
        aria-labelledby="institucionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="institucionModalLabel">
                        {{ $institucion_id ? 'Editar' : 'Crear' }} Institución
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" wire:model.defer="nombre" id="nombre"
                                class="form-control form-control-sm">
                            @error('nombre')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea wire:model.defer="descripcion" id="descripcion" 
                                class="form-control form-control-sm" rows="3"></textarea>
                            @error('descripcion')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                    id="activa" wire:model.defer="activa">
                                <label class="custom-control-label" for="activa">Institución activa</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click.prevent="{{ $institucion_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">{{ $institucion_id ? 'Actualizar' : 'Guardar' }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
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
                                <p class="mb-2"><strong>Creada:</strong> {{ $selectedInstitucion->created_at->format('d/m/Y H:i') }}</p>
                                <p class="mb-2"><strong>Actualizada:</strong> {{ $selectedInstitucion->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Total de Eventuales:</strong> 
                                    <span class="badge badge-info">{{ $selectedInstitucion->eventuales->count() }}</span>
                                </p>
                                @if($selectedInstitucion->eventuales->count() > 0)
                                    <p class="mb-2"><strong>Monto Total:</strong> 
                                        ${{ number_format($selectedInstitucion->eventuales->sum('monto'), 2, ',', '.') }}
                                    </p>
                                @endif
                            </div>
                        </div>
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

            window.addEventListener('show-modal', event => {
                $(event.detail.modal).modal('show');
            });

            window.addEventListener('close-modal', event => {
                $(event.detail.modal).modal('hide');
            });

            window.addEventListener('alert', event => {
                const type = event.detail.type;
                const message = event.detail.message;
                Swal.fire({
                    icon: type,
                    title: message,
                    showConfirmButton: false,
                    timer: 1500
                });
            });

            $(document).ready(function() {
                $('#institucionModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });

                $('#institucionModal').on('shown.bs.modal', function() {
                    $('#nombre').focus();
                });
            });
        </script>
    @endpush
</div>