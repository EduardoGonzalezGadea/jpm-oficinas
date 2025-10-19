<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title">Gestión de Conceptos de Cobro</h5>
                <button wire:click="openModal()" class="btn btn-primary">Crear Concepto</button>
            </div>
        </div>
        <div class="card-body">
            @if (session()->has('message'))
                <div class="alert alert-success">{{ session('message') }}</div>
            @endif
            @if (session()->has('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
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
                            
                            <th wire:click="order('nombre')">Nombre <i class="fas fa-sort"></i></th>
                            <th>Descripción</th>
                            <th wire:click="order('activo')">Activo <i class="fas fa-sort"></i></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conceptos as $concepto)
                            <tr>
                                
                                <td>{{ $concepto->nombre }}</td>
                                <td>{{ $concepto->descripcion }}</td>
                                <td>
                                    <span class="badge badge-{{ $concepto->activo ? 'success' : 'secondary' }}">
                                        {{ $concepto->activo ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td>
                                    <button wire:click="edit({{ $concepto->id }})" class="btn btn-sm btn-primary">Editar</button>
                                    <button wire:click="openCampoModal({{ $concepto->id }})" class="btn btn-sm btn-info">Campos</button>
                                    <button wire:click="delete({{ $concepto->id }})" class="btn btn-sm btn-danger">Eliminar</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $conceptos->links() }}
        </div>
    </div>

    <!-- Modal para Conceptos -->
    @if ($modal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-scrollable" role="document">
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

    <!-- Modal para Campos -->
    @if ($campoModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Configurar Campos del Concepto</h5>
                        <button wire:click="closeCampoModal()" type="button" class="close" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6>Campos Existentes</h6>
                                <div class="list-group">
                                    @foreach ($campos as $campo)
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $campo['titulo'] ?: $campo['nombre'] }}</strong> ({{ $campo['tipo'] }})
                                                <small class="text-muted d-block">Nombre: {{ $campo['nombre'] }}</small>
                                                @if ($campo['requerido'])
                                                    <span class="badge badge-danger">Requerido</span>
                                                @endif
                                            </div>
                                            <div>
                                                <button wire:click="editCampo({{ $campo['id'] }})" class="btn btn-sm btn-primary">Editar</button>
                                                <button wire:click="deleteCampo({{ $campo['id'] }})" class="btn btn-sm btn-danger">Eliminar</button>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if (count($campos) == 0)
                                        <div class="list-group-item text-muted">No hay campos configurados</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>{{ $campoId ? 'Editar' : 'Agregar' }} Campo</h6>
                                <form>
                                    <div class="form-group">
                                        <label for="campoNombre">Nombre</label>
                                        <input type="text" class="form-control" id="campoNombre" wire:model="campoNombre">
                                        @error('campoNombre') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="campoTitulo">Título (Opcional)</label>
                                        <input type="text" class="form-control" id="campoTitulo" wire:model="campoTitulo">
                                        @error('campoTitulo') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="campoTipo">Tipo</label>
                                        <select class="form-control" id="campoTipo" wire:model="campoTipo">
                                            <option value="text">Texto</option>
                                            <option value="number">Número</option>
                                            <option value="date">Fecha</option>
                                            <option value="select">Selección</option>
                                            <option value="textarea">Texto Largo</option>
                                            <option value="checkbox">Checkbox</option>
                                        </select>
                                        @error('campoTipo') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    @if ($campoTipo === 'select')
                                        <div class="form-group">
                                            <label for="campoOpciones">Opciones (una por línea)</label>
                                            <textarea class="form-control" id="campoOpciones" wire:model="campoOpciones" rows="3"></textarea>
                                        </div>
                                    @endif
                                    <div class="form-group">
                                        <label for="campoOrden">Orden</label>
                                        <input type="number" class="form-control" id="campoOrden" wire:model="campoOrden">
                                        @error('campoOrden') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="campoRequerido" wire:model="campoRequerido">
                                        <label class="form-check-label" for="campoRequerido">Requerido</label>
                                    </div>
                                    <button wire:click.prevent="storeCampo()" type="button" class="btn btn-primary btn-block mt-2">
                                        {{ $campoId ? 'Actualizar' : 'Agregar' }} Campo
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeCampoModal()" type="button" class="btn btn-secondary">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
