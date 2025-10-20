
<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">Conceptos de Pago</h5>
                </div>
                <div class="col-md-6 text-right">
                    @can('gestionar_conceptos_pago')
                        <button class="btn btn-primary" wire:click="crearConcepto">
                            <i class="fas fa-plus"></i> Nuevo Concepto
                        </button>
                    @endcan
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar conceptos...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conceptos as $concepto)
                            <tr>
                                <td>{{ $concepto->nombre }}</td>
                                <td>{{ $concepto->descripcion }}</td>
                                <td>
                                    <span class="badge badge-{{ $concepto->activo ? 'success' : 'danger' }}">
                                        {{ $concepto->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @can('gestionar_conceptos_pago')
                                            <button class="btn btn-sm btn-info" wire:click="editarConcepto({{ $concepto->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-{{ $concepto->activo ? 'warning' : 'success' }}"
                                                wire:click="toggleActivo({{ $concepto->id }})"
                                                title="{{ $concepto->activo ? 'Desactivar' : 'Activar' }}">
                                                <i class="fas fa-{{ $concepto->activo ? 'ban' : 'check' }}"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                wire:click="eliminarConcepto({{ $concepto->id }})"
                                                onclick="return confirm('¿Está seguro de eliminar este concepto?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No hay conceptos registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $conceptos->links() }}
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Concepto -->
    <div class="modal fade" wire:ignore.self id="modalConcepto" tabindex="-1" aria-labelledby="modalConceptoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConceptoLabel">{{ $conceptoId ? 'Editar Concepto' : 'Nuevo Concepto' }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="guardarConcepto">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" wire:model="nombre">
                            @error('nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" wire:model="descripcion" rows="3"></textarea>
                            @error('descripcion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="activo" wire:model="activo">
                                <label class="custom-control-label" for="activo">Activo</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="guardarConcepto">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
