<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title">Gestión de Conceptos de Caja</h5>
                <button wire:click="openModal()" class="btn btn-primary">Crear Concepto</button>
            </div>
        </div>
        <div class="card-body">
            @if (session()->has('message'))
                <div class="alert alert-success">{{ session('message') }}</div>
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" wire:model="search" class="form-control" placeholder="Buscar conceptos...">
                </div>
                <div class="col-md-2">
                    <select wire:model="cant" class="form-control">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th wire:click="order('idConcepto')">ID <i class="fas fa-sort"></i></th>
                            <th wire:click="order('nombre')">Nombre <i class="fas fa-sort"></i></th>
                            <th wire:click="order('tipo')">Tipo <i class="fas fa-sort"></i></th>
                            <th>Descripción</th>
                            <th wire:click="order('activo')">Activo <i class="fas fa-sort"></i></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conceptos as $concepto)
                            <tr>
                                <td>{{ $concepto->idConcepto }}</td>
                                <td>{{ $concepto->nombre }}</td>
                                <td>
                                    <span class="badge badge-{{ $concepto->tipo == 'INGRESO' ? 'success' : 'danger' }}">
                                        {{ $concepto->tipo }}
                                    </span>
                                </td>
                                <td>{{ $concepto->descripcion }}</td>
                                <td>
                                    <span class="badge badge-{{ $concepto->activo ? 'success' : 'secondary' }}">
                                        {{ $concepto->activo ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <button wire:click="edit({{ $concepto->idConcepto }})" class="btn btn-sm btn-primary">Editar</button>
                                    <button wire:click="delete({{ $concepto->idConcepto }})" class="btn btn-sm btn-danger">Eliminar</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $conceptos->links() }}
        </div>
    </div>

    @if ($modal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $idConcepto ? 'Editar' : 'Crear' }} Concepto</h5>
                        <button wire:click="closeModal()" type="button" class="close" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" class="form-control" id="nombre" wire:model="nombre">
                                @error('nombre') <span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="tipo">Tipo</label>
                                <select class="form-control" id="tipo" wire:model="tipo">
                                    <option value="">Seleccione un tipo</option>
                                    <option value="INGRESO">Ingreso</option>
                                    <option value="EGRESO">Egreso</option>
                                </select>
                                @error('tipo') <span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control" id="descripcion" wire:model="descripcion"></textarea>
                                @error('descripcion') <span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="activo" wire:model="activo">
                                <label class="form-check-label" for="activo">Activo</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeModal()" type="button" class="btn btn-secondary">Cancelar</button>
                        <button wire:click.prevent="store()" type="button" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
