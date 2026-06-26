<div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><strong><i class="fas fa-building mr-2"></i>Dependencias de Distribución SIIF</strong></h4>
                    <button type="button" class="btn btn-primary" wire:click.prevent="create">
                        <i class="fas fa-plus"></i> Nueva Dependencia
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
                                <input type="text" wire:model="search" id="search-siif-dependencias"
                                    class="form-control"
                                    placeholder="Buscar por dependencia o abreviatura...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Dependencia</th>
                                    <th class="text-center align-middle">Abreviatura</th>
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dependencias as $dep)
                                    <tr>
                                        <td class="align-middle text-left">{{ $dep->dependencia }}</td>
                                        <td class="align-middle text-center"><strong>{{ $dep->abreviatura }}</strong></td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $dep->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsDependenciaModal" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button wire:click="edit({{ $dep->id }})"
                                                class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡Se eliminará la dependencia de distribución SIIF!', method: 'destroy', id: {{ $dep->id }}, confirmButtonText: 'Sí, elimínala' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No hay dependencias de distribución registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $dependencias->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Crear / Editar --}}
    <div wire:ignore.self class="modal fade" id="siifDependenciaModal" tabindex="-1" role="dialog"
        aria-labelledby="siifDependenciaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="siifDependenciaModalLabel">
                        {{ $siif_distribucion_dependencia_id ? 'Editar' : 'Nueva' }} Dependencia de Distribución
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="dependencia">Nombre de la Dependencia *</label>
                            <input type="text"
                                class="form-control @error('dependencia') is-invalid @enderror"
                                wire:model.defer="dependencia"
                                id="dependencia"
                                placeholder="Ej: Jefatura de Policía de Montevideo"
                                required>
                            @error('dependencia')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="abreviatura">Abreviatura *</label>
                            <input type="text"
                                class="form-control @error('abreviatura') is-invalid @enderror"
                                wire:model.defer="abreviatura"
                                id="abreviatura"
                                placeholder="Ej: JPM"
                                required>
                            @error('abreviatura')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button"
                        wire:click.prevent="{{ $siif_distribucion_dependencia_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">
                        {{ $siif_distribucion_dependencia_id ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalles --}}
    <div wire:ignore.self class="modal fade" id="detailsDependenciaModal" tabindex="-1" role="dialog"
        aria-labelledby="detailsDependenciaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsDependenciaModalLabel">Detalles de la Dependencia SIIF</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedDependencia)
                        <p class="mb-1"><strong>Dependencia:</strong> {{ $selectedDependencia->dependencia }}</p>
                        <p class="mb-1"><strong>Abreviatura:</strong> {{ $selectedDependencia->abreviatura }}</p>
                        <hr>
                        <p class="mb-1 text-muted small"><strong>Creado:</strong> {{ $selectedDependencia->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mb-0 text-muted small"><strong>Última actualización:</strong> {{ $selectedDependencia->updated_at?->format('d/m/Y H:i') }}</p>
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
            window.addEventListener('show-modal', event => {
                if (event.detail.id === 'siifDependenciaModal') {
                    $('#siifDependenciaModal').modal('show');
                }
            });

            window.livewire.on('siifDependenciaStore', () => {
                $('#siifDependenciaModal').modal('hide');
            });

            window.livewire.on('siifDependenciaUpdate', () => {
                $('#siifDependenciaModal').modal('hide');
            });

            $(document).ready(function() {
                $('#siifDependenciaModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });
            });
        </script>
    @endpush
</div>
