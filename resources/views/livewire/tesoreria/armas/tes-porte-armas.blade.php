<div>
    <div class="card mt-3">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4>Listado de Porte de Armas</h4>
                </div>
                <div class="col-md-6 text-right">
                    <button wire:click="create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Registro
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if (session()->has('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Buscar..." wire:model="search">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Titular</th>
                            <th>Cédula</th>
                            <th>Orden Cobro</th>
                            <th>N° Trámite</th>
                            <th>Monto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registros as $registro)
                            <tr>
                                <td>{{ $registro->fecha->format('d/m/Y') }}</td>
                                <td>{{ $registro->titular }}</td>
                                <td>{{ $registro->cedula }}</td>
                                <td>{{ $registro->orden_cobro }}</td>
                                <td>{{ $registro->numero_tramite }}</td>
                                <td>$ {{ number_format($registro->monto, 2, ',', '.') }}</td>
                                <td>
                                    <button wire:click="showDetails({{ $registro->id }})" class="btn btn-sm btn-secondary" title="Ver Detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button wire:click="edit({{ $registro->id }})" class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $registro->id }})" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay registros disponibles</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center">
                {{ $registros->links() }}
            </div>
        </div>
    </div>

    <!-- Modal de Crear/Editar -->
    <div class="modal fade @if($showModal) show @endif"
         style="@if($showModal) display: block; @endif"
         tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $editMode ? 'Editar' : 'Nuevo' }} Registro de Porte de Armas
                    </h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="porteForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('fecha') is-invalid @enderror"
                                           wire:model="fecha" id="fecha">
                                    @error('fecha')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Monto <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('monto') is-invalid @enderror"
                                           wire:model="monto" id="monto">
                                    @error('monto')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Titular <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('titular') is-invalid @enderror"
                                           wire:model="titular" id="titular">
                                    @error('titular')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cédula <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('cedula') is-invalid @enderror"
                                           wire:model="cedula" id="cedula">
                                    @error('cedula')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Orden de Cobro</label>
                                    <input type="text" class="form-control @error('orden_cobro') is-invalid @enderror"
                                           wire:model="orden_cobro" id="orden_cobro">
                                    @error('orden_cobro')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Número de Trámite</label>
                                    <input type="text" class="form-control @error('numero_tramite') is-invalid @enderror"
                                           wire:model="numero_tramite" id="numero_tramite">
                                    @error('numero_tramite')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ingreso Contabilidad</label>
                                    <input type="text" class="form-control @error('ingreso_contabilidad') is-invalid @enderror"
                                           wire:model="ingreso_contabilidad" id="ingreso_contabilidad">
                                    @error('ingreso_contabilidad')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Recibo</label>
                                    <input type="text" class="form-control @error('recibo') is-invalid @enderror"
                                           wire:model="recibo" id="recibo">
                                    @error('recibo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" class="form-control @error('telefono') is-invalid @enderror"
                                           wire:model="telefono" id="telefono">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save" id="btnGuardar">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div class="modal fade @if($showDeleteModal) show @endif"
         style="@if($showDeleteModal) display: block; @endif"
         tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="close" wire:click="closeDeleteModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar este registro?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click="delete">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    @if($showModal || $showDeleteModal || $showDetailModal)
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal de Detalle -->
    <div class="modal fade @if($showDetailModal) show @endif" style="@if($showDetailModal) display: block; @endif" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Registro de Porte de Armas</h5>
                    <button type="button" class="close" wire:click="closeDetailModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($selectedRegistro)
                        <div class="row">
                            <div class="col-md-6 mb-2"><strong>Fecha:</strong> {{ $selectedRegistro->fecha->format('d/m/Y') }}</div>
                            <div class="col-md-6 mb-2"><strong>Monto:</strong> $ {{ number_format($selectedRegistro->monto, 2, ',', '.') }}</div>
                            <div class="col-md-6 mb-2"><strong>Titular:</strong> {{ $selectedRegistro->titular }}</div>
                            <div class="col-md-6 mb-2"><strong>Cédula:</strong> {{ $selectedRegistro->cedula }}</div>
                            <div class="col-md-6 mb-2"><strong>Teléfono:</strong> {{ $selectedRegistro->telefono }}</div>
                            <div class="col-md-6 mb-2"><strong>Orden de Cobro:</strong> {{ $selectedRegistro->orden_cobro }}</div>
                            <div class="col-md-6 mb-2"><strong>Número de Trámite:</strong> {{ $selectedRegistro->numero_tramite }}</div>
                            <div class="col-md-6 mb-2"><strong>Ingreso Contabilidad:</strong> {{ $selectedRegistro->ingreso_contabilidad }}</div>
                            <div class="col-md-6 mb-2"><strong>Recibo:</strong> {{ $selectedRegistro->recibo }}</div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            // Manejo del Enter para navegar entre campos
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    const form = document.getElementById('porteForm');
                    if (form) {
                        const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"])'));
                        const index = inputs.indexOf(e.target);

                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        } else {
                            document.getElementById('btnGuardar').focus();
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</div>
