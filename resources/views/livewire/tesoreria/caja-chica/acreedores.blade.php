<div>
    <div class="d-flex justify-content-between mb-3">
        <h5 class="mb-0">Acreedores</h5>
        <button wire:click="create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nuevo Acreedor
        </button>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="mb-3">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
            </div>
            <input type="text" class="form-control" placeholder="Buscar acreedores..." wire:model.debounce.500ms="search">
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
                @forelse($acreedores as $acr)
                    <tr>
                        <td class="align-middle">{{ $acr->acreedor }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button wire:click="edit({{ $acr->idAcreedores }})"
                                    class="btn btn-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $acr->idAcreedores }})"
                                    class="btn btn-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">
                            <i class="fas fa-info-circle"></i> No hay acreedores registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($acreedores->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $acreedores->links() }}
        </div>
    @endif

    <!-- Modal Formulario -->
    @if ($modalFormVisible)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.7); z-index: 9999;" aria-modal="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <form wire:submit.prevent="save">
                        <div class="modal-header">
                            <h6 class="modal-title">
                                <i class="fas {{ $acreedorId ? 'fa-edit' : 'fa-plus' }}"></i>
                                {{ $acreedorId ? 'Editar' : 'Nuevo' }} Acreedor
                            </h6>
                            <button type="button" class="close" wire:click="$set('modalFormVisible', false)">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="nombreAcreedor" class="font-weight-bold">Nombre</label>
                                <input type="text" id="nombreAcreedor" wire:model.defer="nombre"
                                    class="form-control @error('nombre') is-invalid @enderror"
                                    placeholder="Ingrese el nombre del acreedor">
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('modalFormVisible', false)">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Confirmar Eliminación -->
    @if ($modalConfirmDeleteVisible)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.7); z-index: 9999;" aria-modal="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h6 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                        </h6>
                        <button type="button" class="close text-white" wire:click="$set('modalConfirmDeleteVisible', false)">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            <i class="fas fa-info-circle text-info"></i>
                            ¿Está seguro que desea eliminar este acreedor?
                        </p>
                        <p class="text-danger font-weight-bold mt-2 mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            Esta acción no se puede deshacer.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('modalConfirmDeleteVisible', false)">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="button" wire:click="delete" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>