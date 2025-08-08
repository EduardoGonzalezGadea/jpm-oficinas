<div>
    <div class="d-flex justify-content-between mb-3">
        <h4>Gestión de Dependencias</h4>
        <div class="btn-group">
            <button wire:click="create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Dependencia
            </button>
            <button wire:click="cerrarGestionDependencias" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="mb-3">
        <input type="text" class="form-control" placeholder="Buscar..." wire:model.debounce.500ms="search">
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th width="150">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dependencias as $dep)
                <tr>
                    <td>{{ $dep->dependencia }}</td>
                    <td>
                        <button wire:click="edit({{ $dep->idDependencias }})"
                            class="btn btn-sm btn-warning">Editar</button>
                        <button wire:click="confirmDelete({{ $dep->idDependencias }})"
                            class="btn btn-sm btn-danger">Eliminar</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No hay dependencias registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $dependencias->links() }}
    </div>

    <!-- Modal Formulario -->
    @if ($modalFormVisible)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);"
            aria-modal="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form wire:submit.prevent="save">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $dependenciaId ? 'Editar Dependencia' : 'Nueva Dependencia' }}
                            </h5>
                            <button type="button" class="close"
                                wire:click="$set('modalFormVisible', false)"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" wire:model.defer="nombre"
                                    class="form-control @error('nombre') is-invalid @enderror">
                                @error('nombre')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                wire:click="$set('modalFormVisible', false)">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Confirmar Eliminación -->
    @if ($modalConfirmDeleteVisible)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);"
            aria-modal="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar Dependencia</h5>
                        <button type="button" class="close"
                            wire:click="$set('modalConfirmDeleteVisible', false)"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        ¿Está seguro que desea eliminar esta dependencia?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="$set('modalConfirmDeleteVisible', false)">Cancelar</button>
                        <button type="button" wire:click="delete" class="btn btn-danger">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
