<div>
    <div class="d-flex justify-content-between mb-3">
        <h5 class="mb-0">Dependencias</h5>
        <button wire:click="create" class="btn btn-primary btn-sm" type="button">
            <i class="fas fa-plus"></i> Nueva Dependencia
        </button>
    </div>

    <div class="mb-3">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <input type="text" class="form-control" placeholder="Buscar dependencias..." wire:model.debounce.500ms="search">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Nombre</th>
                    <th width="120" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dependencias as $dep)
                    <tr>
                        <td class="align-middle">{{ $dep->dependencia }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button wire:click="edit({{ $dep->idDependencias }})"
                                    class="btn btn-warning" title="Editar" type="button">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $dep->idDependencias }})"
                                    class="btn btn-danger" title="Eliminar" type="button">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">
                            <i class="fas fa-info-circle"></i> No hay dependencias registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($dependencias->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $dependencias->links() }}
        </div>
    @endif

    <!-- Modal Formulario -->
    <div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-labelledby="formModalLabel" aria-hidden="true" style="z-index: 1060;" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="z-index: 1061;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalLabel">{{ $dependenciaId ? 'Editar Dependencia' : 'Nueva Dependencia' }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nombreDependencia" class="font-weight-bold">Nombre</label>
                            <input type="text" id="nombreDependencia" wire:model.defer="nombre"
                                class="form-control @error('nombre') is-invalid @enderror"
                                placeholder="Ingrese el nombre de la dependencia">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal('formModal')">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" wire:click="save" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                            <i class="fas fa-save"></i>
                            <span wire:loading.remove wire:target="save">Guardar</span>
                            <span wire:loading wire:target="save">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true" style="z-index: 1060;" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="z-index: 1061;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        <i class="fas fa-info-circle text-info"></i>
                        ¿Está seguro que desea eliminar esta dependencia?
                    </p>
                    <p class="text-danger font-weight-bold mt-2 mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal('deleteModal')">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" wire:click="delete" class="btn btn-danger btn-sm" wire:loading.attr="disabled" wire:target="delete">
                        <i class="fas fa-trash"></i>
                        <span wire:loading.remove wire:target="delete">Eliminar</span>
                        <span wire:loading wire:target="delete">Eliminando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('formModal-show', function () {
                $('#formModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
            });

            window.addEventListener('formModal-hide', function () {
                $('#formModal').modal('hide');
            });

            window.addEventListener('deleteModal-show', function () {
                $('#deleteModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
            });

            window.addEventListener('deleteModal-hide', function () {
                $('#deleteModal').modal('hide');
            });

            // Debug listeners to track events
            console.log('DEBUG: Event listeners setup for dependencias');
        });
    </script>
</div>
