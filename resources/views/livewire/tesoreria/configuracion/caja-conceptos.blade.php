<div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><strong><i class="fas fa-box mr-2"></i>Conceptos de Caja</strong></h4>
                    <button type="button" class="btn btn-primary" wire:click.prevent="create">
                        <i class="fas fa-plus"></i> Nuevo Concepto
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
                                <input type="text" wire:model="search" id="search-caja-conceptos"
                                    class="form-control"
                                    placeholder="Buscar por nombre del concepto...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Concepto</th>
                                    <th class="text-center align-middle">Tipo SIIF</th>
                                    <th class="text-center align-middle">Req. Confirmación</th>
                                    <th class="text-center align-middle">Req. Distribución</th>
                                    <th class="text-center align-middle">Permite Planilla</th>
                                    <th class="text-center align-middle">Req. Organismo</th>
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($conceptos as $concepto)
                                    <tr>
                                        <td class="align-middle">{{ $concepto->caja_concepto }}</td>
                                        <td class="text-center align-middle">
                                            @if ($concepto->siifDistribucionTipo)
                                                <span class="badge badge-primary" title="{{ $concepto->siifDistribucionTipo->tipo }}">
                                                    {{ $concepto->siifDistribucionTipo->tipo }}
                                                </span>
                                            @else
                                                <span class="badge badge-light text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input type="checkbox" class="custom-control-input"
                                                    wire:click="toggleConfirmacion({{ $concepto->id }})"
                                                    id="confirmacion-switch-{{ $concepto->id }}"
                                                    @if($concepto->requiere_confirmacion) checked @endif>
                                                <label class="custom-control-label" for="confirmacion-switch-{{ $concepto->id }}">
                                                    <span class="badge badge-{{ $concepto->requiere_confirmacion ? 'warning' : 'secondary' }}">
                                                        {{ $concepto->requiere_confirmacion ? 'Sí' : 'No' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input type="checkbox" class="custom-control-input"
                                                    wire:click="toggleDistribucion({{ $concepto->id }})"
                                                    id="distribucion-switch-{{ $concepto->id }}"
                                                    @if($concepto->requiere_distribucion) checked @endif>
                                                <label class="custom-control-label" for="distribucion-switch-{{ $concepto->id }}">
                                                    <span class="badge badge-{{ $concepto->requiere_distribucion ? 'info' : 'secondary' }}">
                                                        {{ $concepto->requiere_distribucion ? 'Sí' : 'No' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input type="checkbox" class="custom-control-input"
                                                    wire:click="togglePlanilla({{ $concepto->id }})"
                                                    id="planilla-switch-{{ $concepto->id }}"
                                                    @if($concepto->permite_planilla) checked @endif>
                                                <label class="custom-control-label" for="planilla-switch-{{ $concepto->id }}">
                                                    <span class="badge badge-{{ $concepto->permite_planilla ? 'success' : 'secondary' }}">
                                                        {{ $concepto->permite_planilla ? 'Sí' : 'No' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="custom-control custom-switch d-inline-block">
                                                <input type="checkbox" class="custom-control-input"
                                                    wire:click="toggleOrganismo({{ $concepto->id }})"
                                                    id="organismo-switch-{{ $concepto->id }}"
                                                    @if($concepto->requiere_organismo) checked @endif>
                                                <label class="custom-control-label" for="organismo-switch-{{ $concepto->id }}">
                                                    <span class="badge badge-{{ $concepto->requiere_organismo ? 'primary' : 'secondary' }}">
                                                        {{ $concepto->requiere_organismo ? 'Sí' : 'No' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $concepto->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsConceptoModal" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button wire:click="edit({{ $concepto->id }})"
                                                class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡Se eliminará el concepto de caja!', method: 'destroy', id: {{ $concepto->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No hay conceptos de caja registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $conceptos->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Crear / Editar --}}
    <div wire:ignore.self class="modal fade" id="cajaConceptoModal" tabindex="-1" role="dialog"
        aria-labelledby="cajaConceptoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cajaConceptoModalLabel">
                        {{ $caja_concepto_id ? 'Editar' : 'Nuevo' }} Concepto de Caja
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-group">
                            <label for="caja_concepto">Nombre del Concepto *</label>
                            <input type="text"
                                class="form-control @error('caja_concepto') is-invalid @enderror"
                                wire:model.defer="caja_concepto"
                                id="caja_concepto"
                                placeholder="Ej: MULTAS DE TRÁNSITO"
                                required>
                            @error('caja_concepto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="siif_distribucion_tipo_id">Tipo de Distribución SIIF</label>
                            <select class="form-control @error('siif_distribucion_tipo_id') is-invalid @enderror"
                                wire:model.defer="siif_distribucion_tipo_id"
                                id="siif_distribucion_tipo_id">
                                <option value="">— Sin tipo SIIF —</option>
                                @foreach ($siifTipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->tipo }}</option>
                                @endforeach
                            </select>
                            @error('siif_distribucion_tipo_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Tipo de distribución SIIF que se aplicará por defecto a este concepto.</small>
                        </div>

                        <div class="form-group">
                            <label class="d-block mb-1">Opciones</label>
                            <div class="custom-control custom-switch mb-2">
                                <input type="checkbox" class="custom-control-input"
                                    wire:model.defer="requiere_confirmacion"
                                    id="requiere_confirmacion">
                                <label class="custom-control-label" for="requiere_confirmacion">
                                    Requiere Confirmación
                                </label>
                                <small class="form-text text-muted">El cobro debe ser confirmado antes de registrarse.</small>
                            </div>
                            <div class="custom-control custom-switch mb-2">
                                <input type="checkbox" class="custom-control-input"
                                    wire:model.defer="requiere_distribucion"
                                    id="requiere_distribucion">
                                <label class="custom-control-label" for="requiere_distribucion">
                                    Requiere Distribución
                                </label>
                                <small class="form-text text-muted">El monto debe distribuirse entre dependencias.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input"
                                    wire:model.defer="permite_planilla"
                                    id="permite_planilla">
                                <label class="custom-control-label" for="permite_planilla">
                                    Permite Planilla
                                </label>
                                <small class="form-text text-muted">Se pueden generar planillas para este concepto.</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input"
                                    wire:model.defer="requiere_organismo"
                                    id="requiere_organismo">
                                <label class="custom-control-label" for="requiere_organismo">
                                    Requiere Organismo
                                </label>
                                <small class="form-text text-muted">El concepto requiere seleccionar un organismo/entidad.</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button"
                        wire:click.prevent="{{ $caja_concepto_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">
                        {{ $caja_concepto_id ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalles --}}
    <div wire:ignore.self class="modal fade" id="detailsConceptoModal" tabindex="-1" role="dialog"
        aria-labelledby="detailsConceptoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsConceptoModalLabel">Detalles del Concepto de Caja</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedConcepto)
                        <p class="mb-1"><strong>Concepto:</strong> {{ $selectedConcepto->caja_concepto }}</p>
                        <p class="mb-1"><strong>Tipo SIIF:</strong>
                            @if ($selectedConcepto->siifDistribucionTipo)
                                <span class="badge badge-primary">{{ $selectedConcepto->siifDistribucionTipo->tipo }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </p>
                        <p class="mb-1"><strong>Requiere Confirmación:</strong>
                            <span class="badge badge-{{ $selectedConcepto->requiere_confirmacion ? 'warning' : 'secondary' }}">
                                {{ $selectedConcepto->requiere_confirmacion ? 'Sí' : 'No' }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Requiere Distribución:</strong>
                            <span class="badge badge-{{ $selectedConcepto->requiere_distribucion ? 'info' : 'secondary' }}">
                                {{ $selectedConcepto->requiere_distribucion ? 'Sí' : 'No' }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Permite Planilla:</strong>
                            <span class="badge badge-{{ $selectedConcepto->permite_planilla ? 'success' : 'secondary' }}">
                                {{ $selectedConcepto->permite_planilla ? 'Sí' : 'No' }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Requiere Organismo:</strong>
                            <span class="badge badge-{{ $selectedConcepto->requiere_organismo ? 'primary' : 'secondary' }}">
                                {{ $selectedConcepto->requiere_organismo ? 'Sí' : 'No' }}
                            </span>
                        </p>
                        <hr>
                        <p class="mb-1 text-muted small"><strong>Creado:</strong> {{ $selectedConcepto->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mb-0 text-muted small"><strong>Última actualización:</strong> {{ $selectedConcepto->updated_at?->format('d/m/Y H:i') }}</p>
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
            window.addEventListener('swal:confirm', event => {
                Swal.fire({
                    title: event.detail.title,
                    text: event.detail.text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: event.detail.confirmButtonText,
                    cancelButtonText: 'Cancelar',
                    focusConfirm: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.call(event.detail.method, event.detail.id);
                    }
                });
            });

            window.addEventListener('show-modal', event => {
                $('#' + event.detail.id).modal('show');
            });

            window.addEventListener('alert', event => {
                const type = event.detail.type;
                const message = event.detail.message;
                const isToast = event.detail.toast || false;

                if (isToast) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        icon: type,
                        title: message,
                    });
                } else {
                    Swal.fire({
                        icon: type,
                        title: message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });

            window.livewire.on('cajaConceptoStore', () => {
                $('#cajaConceptoModal').modal('hide');
            });

            window.livewire.on('cajaConceptoUpdate', () => {
                $('#cajaConceptoModal').modal('hide');
            });

            $(document).ready(function() {
                $('#cajaConceptoModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });
            });
        </script>
    @endpush
</div>
