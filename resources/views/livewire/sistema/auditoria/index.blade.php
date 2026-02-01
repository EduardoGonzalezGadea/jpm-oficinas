<div class="container-fluid px-0">
    @section('title', 'Historial de Auditoría')

    <div class="card">
        <div class="card-header bg-dark text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-history mr-2"></i>Historial de Auditoría</strong>
                </h4>
                <span class="badge badge-light">
                    <i class="fas fa-database mr-1"></i>{{ $totalRegistros }} registros
                </span>
            </div>
        </div>

        <div class="card-body px-2 pt-2">
            {{-- Filtros --}}
            <div class="row mb-3 d-print-none">
                {{-- Búsqueda general --}}
                <div class="col-md-4 mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.debounce.400ms="search" class="form-control"
                            placeholder="Buscar en descripción o datos...">
                    </div>
                </div>

                {{-- Filtro por evento --}}
                <div class="col-md-2 mb-2">
                    <select wire:model="event" class="form-control">
                        <option value="">Todos los eventos</option>
                        <option value="created">Creado</option>
                        <option value="updated">Actualizado</option>
                        <option value="deleted">Eliminado</option>
                        <option value="restored">Restaurado</option>
                        <option value="login">Inicio Sesión</option>
                        <option value="logout">Cierre Sesión</option>
                    </select>
                </div>

                {{-- Filtro por tipo de registro --}}
                <div class="col-md-2 mb-2">
                    <select wire:model="subjectType" class="form-control">
                        <option value="">Todos los tipos</option>
                        @foreach($subjectTypes as $type)
                        <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtro por usuario --}}
                <div class="col-md-2 mb-2">
                    <select wire:model="causerId" class="form-control">
                        <option value="">Todos los usuarios</option>
                        @foreach($users as $user)
                        <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Registros por página --}}
                <div class="col-md-2 mb-2">
                    <select wire:model="perPage" class="form-control">
                        <option value="10">10 por página</option>
                        <option value="25">25 por página</option>
                        <option value="50">50 por página</option>
                        <option value="100">100 por página</option>
                    </select>
                </div>
            </div>

            {{-- Segunda fila de filtros --}}
            <div class="row mb-3 d-print-none">
                {{-- Fecha desde --}}
                <div class="col-md-3 mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                        </div>
                        <input type="date" wire:model="dateFrom" class="form-control" title="Desde">
                    </div>
                </div>

                {{-- Fecha hasta --}}
                <div class="col-md-3 mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                        </div>
                        <input type="date" wire:model="dateTo" class="form-control" title="Hasta">
                    </div>
                </div>

                {{-- Filtro por módulo/log --}}
                <div class="col-md-3 mb-2">
                    <select wire:model="logName" class="form-control">
                        <option value="">Todos los módulos</option>
                        @foreach($logNames as $name)
                        <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Botón limpiar filtros --}}
                <div class="col-md-3 mb-2">
                    <button wire:click="clearFilters" class="btn btn-outline-danger btn-block">
                        <i class="fas fa-times mr-1"></i>Limpiar Filtros
                    </button>
                </div>
            </div>

            {{-- Tabla de actividades --}}
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th class="align-middle" style="width: 140px;">Fecha/Hora</th>
                            <th class="align-middle" style="width: 120px;">Evento</th>
                            <th class="align-middle" style="width: 150px;">Tipo</th>
                            <th class="align-middle">Descripción</th>
                            <th class="align-middle" style="width: 150px;">Usuario</th>
                            <th class="align-middle text-center d-print-none" style="width: 80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($activities as $activity)
                        <tr>
                            <td class="align-middle">
                                <small>
                                    <i class="fas fa-clock text-muted mr-1"></i>
                                    {{ $activity->created_at->format('d/m/Y H:i:s') }}
                                </small>
                            </td>
                            <td class="align-middle">
                                <span class="badge {{ $this->getEventBadgeClass($activity->event) }}">
                                    {{ $this->getEventLabel($activity->event) }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-secondary">
                                    {{ $this->getSubjectLabel($activity->subject_type) }}
                                </span>
                                @if($activity->subject_id)
                                <small class="text-muted">#{{ $activity->subject_id }}</small>
                                @endif
                            </td>
                            <td class="align-middle">
                                {{ Str::limit($activity->description, 80) }}
                            </td>
                            <td class="align-middle">
                                @if($activity->causer)
                                <i class="fas fa-user text-muted mr-1"></i>
                                {{ $activity->causer->nombre_completo ?? 'N/A' }}
                                @else
                                <span class="text-muted"><i class="fas fa-robot mr-1"></i>Sistema</span>
                                @endif
                            </td>
                            <td class="align-middle text-center d-print-none">
                                <button class="btn btn-sm btn-info" title="Ver Detalles"
                                    wire:click="showDetail({{ $activity->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No se encontraron registros de auditoría con los filtros aplicados.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="mt-3 d-flex justify-content-center d-print-none">
                {{ $activities->links() }}
            </div>
        </div>
    </div>

    {{-- Modal de Detalle --}}
    @if($selectedActivity)
    <div class="modal fade show" id="detailModal" tabindex="-1" role="dialog"
        style="display: {{ $showDetailModal ? 'block' : 'none' }}; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle mr-2"></i>Detalle de Auditoría #{{ $selectedActivity->id }}
                    </h5>
                    <button type="button" class="close text-white" wire:click="closeDetailModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Información general --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><i class="fas fa-calendar-alt mr-1"></i>Fecha y Hora:</strong>
                            <p>{{ $selectedActivity->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-tag mr-1"></i>Evento:</strong>
                            <p>
                                <span class="badge {{ $this->getEventBadgeClass($selectedActivity->event) }}">
                                    {{ $this->getEventLabel($selectedActivity->event) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><i class="fas fa-user mr-1"></i>Realizado por:</strong>
                            <p>
                                @if($selectedActivity->causer)
                                {{ $selectedActivity->causer->nombre_completo }}
                                <br><small class="text-muted">{{ $selectedActivity->causer->email }}</small>
                                @else
                                <span class="text-muted">Sistema</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-file mr-1"></i>Tipo de Registro:</strong>
                            <p>
                                {{ $this->getSubjectLabel($selectedActivity->subject_type) }}
                                @if($selectedActivity->subject_id)
                                <span class="badge badge-light">#{{ $selectedActivity->subject_id }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-align-left mr-1"></i>Descripción:</strong>
                        <p>{{ $selectedActivity->description }}</p>
                    </div>

                    @if($selectedActivity->log_name)
                    <div class="mb-3">
                        <strong><i class="fas fa-folder mr-1"></i>Módulo:</strong>
                        <p><span class="badge badge-info">{{ ucfirst($selectedActivity->log_name) }}</span></p>
                    </div>
                    @endif

                    {{-- Propiedades / Cambios --}}
                    @if($selectedActivity->properties && $selectedActivity->properties->count() > 0)
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-exchange-alt mr-1"></i>Cambios Realizados</h6>

                    @if($selectedActivity->properties->has('old') && $selectedActivity->properties->has('attributes'))
                    {{-- Mostrar comparativa de cambios --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Campo</th>
                                    <th>Valor Anterior</th>
                                    <th>Valor Nuevo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedActivity->properties->get('attributes', []) as $key => $newValue)
                                @php
                                $oldValue = $selectedActivity->properties->get('old')[$key] ?? '-';

                                // Traducción de campos comunes
                                $displayKey = match($key) {
                                'updated_at' => 'Actualizado en',
                                'created_at' => 'Creado en',
                                'deleted_at' => 'Eliminado en',
                                'email_verified_at' => 'Email verificado en',
                                'last_login' => 'Último acceso',
                                default => ucfirst(str_replace('_', ' ', $key))
                                };

                                // Función para formatear fechas si el valor parece ser una
                                $formatDate = function($value) {
                                if (!is_string($value)) return $value;

                                // Detectar formatos comunes de fecha/hora de base de datos
                                if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $value)) {
                                try {
                                return \Carbon\Carbon::parse($value)->format('d/m/Y H:i:s');
                                } catch (\Exception $e) {
                                return $value;
                                }
                                }
                                return $value;
                                };

                                $oldValue = $formatDate($oldValue);
                                $newValue = $formatDate($newValue);
                                @endphp
                                <tr>
                                    <td><strong>{{ $displayKey }}</strong></td>
                                    <td class="text-danger">
                                        @if(is_array($oldValue))
                                        <code>{{ json_encode($oldValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
                                        @else
                                        {{ $oldValue }}
                                        @endif
                                    </td>
                                    <td class="text-success">
                                        @if(is_array($newValue))
                                        <code>{{ json_encode($newValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
                                        @else
                                        {{ $newValue }}
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($selectedActivity->properties->has('attributes'))
                    {{-- Solo nuevos atributos (creación) --}}
                    <div class="card bg-light">
                        <div class="card-body">
                            <pre class="mb-0" style="max-height: 400px; overflow-y: auto;">{{ json_encode($selectedActivity->properties->get('attributes'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                    @else
                    {{-- Mostrar todas las propiedades --}}
                    <div class="card bg-light">
                        <div class="card-body">
                            <pre class="mb-0" style="max-height: 400px; overflow-y: auto;">{{ json_encode($selectedActivity->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">
                        <i class="fas fa-times mr-1"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
        /* Asegurar que el modal manual sea scrolleable si es muy largo */
        #detailModal {
            overflow-y: auto !important;
        }
    </style>
</div>

@push('scripts')
<script>
    window.addEventListener('show-detail-modal', event => {
        // Prevenir scroll del body cuando el modal está abierto
        document.body.classList.add('modal-open');
    });

    Livewire.on('closeDetailModal', () => {
        document.body.classList.remove('modal-open');
    });
</script>
@endpush