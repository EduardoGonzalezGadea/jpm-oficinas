<div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><strong><i class="fas fa-tags mr-2"></i>Tipos de Distribución SIIF</strong></h4>
                    <button type="button" class="btn btn-primary" wire:click.prevent="create">
                        <i class="fas fa-plus"></i> Nuevo Tipo
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
                                <input type="text" wire:model="search" id="search-siif-tipos"
                                    class="form-control"
                                    placeholder="Buscar por tipo...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Tipo</th>
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tipos as $item)
                                    <tr>
                                        <td class="align-middle text-left">{{ $item->tipo }}</td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $item->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsTipoModal" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button wire:click="edit({{ $item->id }})"
                                                class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡Se eliminará el tipo de distribución SIIF!', method: 'destroy', id: {{ $item->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">No hay tipos de distribución registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $tipos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Crear / Editar --}}
    <div wire:ignore.self class="modal fade" id="siifTipoModal" tabindex="-1" role="dialog"
        aria-labelledby="siifTipoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="siifTipoModalLabel">
                        {{ $siif_distribucion_tipo_id ? 'Editar' : 'Nuevo' }} Tipo de Distribución
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="tipo">Nombre del Tipo *</label>
                            <input type="text"
                                class="form-control @error('tipo') is-invalid @enderror"
                                wire:model.defer="tipo"
                                id="tipo"
                                placeholder="Ej: Recaudación Artículo 222"
                                required>
                            @error('tipo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button"
                        wire:click.prevent="{{ $siif_distribucion_tipo_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">
                        {{ $siif_distribucion_tipo_id ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalles --}}
    <div wire:ignore.self class="modal fade" id="detailsTipoModal" tabindex="-1" role="dialog"
        aria-labelledby="detailsTipoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsTipoModalLabel">Detalles del Tipo de Distribución SIIF</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedTipo)
                        <p class="mb-1"><strong>Tipo:</strong> {{ $selectedTipo->tipo }}</p>
                        <hr>
                        <p class="mb-1 text-muted small"><strong>Creado:</strong> {{ $selectedTipo->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mb-0 text-muted small"><strong>Última actualización:</strong> {{ $selectedTipo->updated_at?->format('d/m/Y H:i') }}</p>
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
                if (event.detail.id === 'siifTipoModal') {
                    $('#siifTipoModal').modal('show');
                }
            });

            window.livewire.on('siifTipoStore', () => {
                $('#siifTipoModal').modal('hide');
            });

            window.livewire.on('siifTipoUpdate', () => {
                $('#siifTipoModal').modal('hide');
            });

            $(document).ready(function() {
                $('#siifTipoModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });
            });
        </script>
    @endpush
</div>
