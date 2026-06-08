<div>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-uppercase font-weight-bold"><i class="fas fa-project-diagram mr-2"></i>Definiciones de Estados de Recaudación (ER)</h5>
            <button class="btn btn-sm btn-success px-3" wire:click="create">
                <i class="fas fa-plus mr-1"></i> Nueva Definición
            </button>
        </div>
        <div class="card-body bg-light">
            <div class="row mb-4">
                <div class="col-md-5">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input type="text" class="form-control border-left-0" placeholder="Buscar por nombre o código..." wire:model.debounce.300ms="search">
                    </div>
                </div>
            </div>

            <div class="table-responsive shadow-sm rounded bg-white">
                <table class="table table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th width="80">Orden</th>
                            <th>Definición / Código</th>
                            <th>Tipo / Turno</th>
                            <th>Unidad / Institución</th>
                            <th>Conceptos Asociados</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-dark">
                        @forelse($definiciones as $def)
                        <tr>
                            <td class="font-weight-bold text-center border-right bg-light">{{ $def->orden }}</td>
                            <td>
                                <div class="font-weight-bold text-primary">{{ $def->nombre }}</div>
                                <small class="text-muted font-weight-bold">{{ $def->codigo }}</small>
                            </td>
                            <td>
                                <span class="badge badge-pill badge-info px-2 py-1">{{ $def->tipo_recaudacion }}</span>
                                @if($def->turno)
                                <small class="d-block mt-1 font-weight-bold text-secondary text-uppercase">{{ $def->turno }}</small>
                                @endif
                            </td>
                            <td>
                                @if($def->tipo_recaudacion == '222')
                                <span class="text-dark">{{ $def->institucion222 ? $def->institucion222->nombre : 'Cualquier Institución' }}</span>
                                @else
                                <span class="text-muted italic">Libre Disponibilidad</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap" style="gap: 4px;">
                                    @forelse($def->conceptos as $concepto)
                                    <span class="badge badge-outline-dark badge-light border text-truncate" style="max-width: 150px;" title="{{ $concepto->nombre }}">
                                        {{ $concepto->nombre }}
                                    </span>
                                    @empty
                                    <small class="text-danger italic">Sin conceptos</small>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <span class="badge badge-{{ $def->activo ? 'success' : 'danger' }} px-3 py-1">
                                    {{ $def->activo ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </td>
                            <td class="text-center align-middle">
                                <div class="btn-group shadow-sm border rounded overflow-hidden">
                                    <button class="btn btn-sm btn-white border-0 py-2" wire:click="edit({{ $def->id }})" title="Editar">
                                        <i class="fas fa-pencil-alt text-primary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-white border-0 py-2 border-left" wire:click="confirmDelete({{ $def->id }})" title="Eliminar">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
                                    No se encontraron definiciones de ER.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $definiciones->links() }}
            </div>
        </div>
    </div>

    {{-- Modal CRUD --}}
    <div class="modal fade" id="modalERDefinicion" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-{{ $er_definicion_id ? 'edit' : 'plus-circle' }} mr-2"></i>
                        {{ $er_definicion_id ? 'EDITAR' : 'REGLA DE' }} DEFINICIÓN DE ER
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form wire:submit.prevent="store">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-4">
                                    <label class="font-weight-bold text-dark">Nombre de la Definición <span class="text-danger font-weight-bold">*</span></label>
                                    <input type="text" class="form-control form-control-lg border shadow-sm @error('nombre') is-invalid @enderror" wire:model.defer="nombre" placeholder="Ej: RECAUDACIÓN DIARIA 222 JPM">
                                    <small class="text-muted">Nombre descriptivo que aparecerá en los listados.</small>
                                    @error('nombre') <span class="invalid-feedback font-weight-bold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-4">
                                    <label class="font-weight-bold text-dark">Código / ID <span class="text-danger font-weight-bold">*</span></label>
                                    <input type="text" class="form-control form-control-lg text-uppercase font-weight-bold border shadow-sm @error('codigo') is-invalid @enderror" wire:model.defer="codigo" placeholder="Ej: ER-222-JPM">
                                    @error('codigo') <span class="invalid-feedback font-weight-bold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row border rounded p-3 bg-white mb-4 mx-0 shadow-sm">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold text-muted">Tipo Recaudación</label>
                                    <select class="form-control" wire:model="tipo_recaudacion">
                                        <option value="LD">LIBRE DISPONIBILIDAD</option>
                                        <option value="222">ART. 222</option>
                                    </select>
                                </div>
                            </div>

                            @if($tipo_recaudacion == '222')
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold text-muted text-truncate w-100">Unidad Ejecutora / Institución</label>
                                    <select class="form-control shadow-sm" wire:model.defer="institucion_222_id">
                                        <option value="">TODAS LAS INSTITUCIONES</option>
                                        @foreach($instituciones as $inst)
                                        <option value="{{ $inst->id }}">{{ $inst->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold text-muted">Filtro de Turno</label>
                                    <select class="form-control shadow-sm text-uppercase" wire:model.defer="turno">
                                        <option value="">TODOS LOS TURNOS</option>
                                        <option value="DIURNO">DIURNO</option>
                                        <option value="NOCTURNO">NOCTURNO</option>
                                        <option value="SABADO">SÁBADO</option>
                                        <option value="DOMINGO">DOMINGO</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group border rounded p-3 bg-white shadow-sm mb-4">
                            <label class="font-weight-bold text-dark h6 mb-3"><i class="fas fa-list-ul mr-2 text-primary"></i>CONCEPTOS QUE DISPARAN ESTE ER</label>
                            <div class="row overflow-auto" style="max-height: 250px;">
                                @foreach($conceptos as $concepto)
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-checkbox p-2 border rounded-pill hover-bg-light transition-all px-4">
                                        <input type="checkbox" class="custom-control-input" id="conc_{{ $concepto->id }}"
                                            value="{{ $concepto->id }}" wire:model.defer="conceptos_seleccionados">
                                        <label class="custom-control-label font-weight-500 cursor-pointer w-100" for="conc_{{ $concepto->id }}">
                                            {{ $concepto->nombre }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @if(empty($conceptos_seleccionados))
                            <div class="alert alert-warning py-2 mt-2 border-0 small">
                                <i class="fas fa-exclamation-triangle mr-2"></i> Debe seleccionar al menos un concepto para que este ER se genere automáticamente.
                            </div>
                            @endif
                        </div>

                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark">Prioridad / Orden</label>
                                    <input type="number" class="form-control border shadow-sm" wire:model.defer="orden">
                                </div>
                            </div>
                            <div class="col-md-4 offset-md-4 text-right">
                                <div class="custom-control custom-switch custom-switch-lg custom-switch-success">
                                    <input type="checkbox" class="custom-control-input" id="activoSwitch" wire:model.defer="activo">
                                    <label class="custom-control-label font-weight-bold pt-1 cursor-pointer" for="activoSwitch text-uppercase">
                                        {{ $activo ? 'ER ACTIVO' : 'ER INACTIVO' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary px-4 font-weight-bold" data-dismiss="modal">CANCELAR</button>
                    <button type="button" class="btn btn-primary px-5 font-weight-bold shadow" wire:click="store" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="store"><i class="fas fa-save mr-2"></i>GUARDAR CONFIGURACIÓN</span>
                        <span wire:loading wire:target="store"><span class="spinner-border spinner-border-sm mr-2"></span>PROCESANDO...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-bg-light:hover {
            background-color: #f8f9fa;
        }

        .font-weight-500 {
            font-weight: 500;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .transition-all {
            transition: all 0.2s;
        }

        .badge-outline-dark {
            border-color: #343a40 !important;
            color: #343a40;
            background: transparent;
        }

        .modal-header .close {
            opacity: 1;
        }
    </style>

    <script>
        window.addEventListener('openModal', event => {
            $('#' + event.detail.id).modal('show');
        });
        window.addEventListener('closeModal', event => {
            $('#' + event.detail.id).modal('hide');
        });
    </script>
</div>