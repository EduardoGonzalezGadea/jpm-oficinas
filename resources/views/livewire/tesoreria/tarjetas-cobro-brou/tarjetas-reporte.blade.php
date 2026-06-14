<div class="reporte-avanzado-container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-print mr-2"></i>Reporte de Tarjetas de Cobro BROU</h5>
                    <a href="{{ route('tesoreria.tarjetas-cobro-brou.index') }}" class="btn btn-sm btn-light text-dark font-weight-bold">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a la Vista Principal
                    </a>
                </div>
                <div class="card-body py-2">
                    <form wire:submit.prevent="buscar">
                        <div class="row">
                            <!-- Grupo 1: Cronología y Fechas -->
                            <div class="col-md-12">
                                <div class="card mb-2">
                                    <div class="card-header bg-light py-1 px-2 border-bottom-0">
                                        <strong><small><i class="fas fa-calendar-alt mr-1"></i> Cronología de Trámites (Fechas)</small></strong>
                                    </div>
                                    <div class="card-body py-2 px-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="mb-0 small font-weight-bold text-primary">Fecha de Recibido</label>
                                                <div class="form-row">
                                                    <div class="col-6 mb-1">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">Desde</span></div>
                                                            <input type="date" class="form-control" wire:model.defer="filters.fecha_desde">
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mb-1">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">Hasta</span></div>
                                                            <input type="date" class="form-control" wire:model.defer="filters.fecha_hasta">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="mb-0 small font-weight-bold text-success">Fecha de Entregado</label>
                                                <div class="form-row">
                                                    <div class="col-6 mb-1">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">Desde</span></div>
                                                            <input type="date" class="form-control" wire:model.defer="filters.fecha_entregado_desde">
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mb-1">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">Hasta</span></div>
                                                            <input type="date" class="form-control" wire:model.defer="filters.fecha_entregado_hasta">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="mb-0 small font-weight-bold text-danger">Fecha de Devuelto</label>
                                                <div class="form-row">
                                                    <div class="col-6 mb-1">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">Desde</span></div>
                                                            <input type="date" class="form-control" wire:model.defer="filters.fecha_devuelto_desde">
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mb-1">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">Hasta</span></div>
                                                            <input type="date" class="form-control" wire:model.defer="filters.fecha_devuelto_hasta">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row border-top mt-2 pt-2">
                                            <div class="col-md-3 mb-1">
                                                <label class="mb-0 small">Estado Actual</label>
                                                <select class="form-control form-control-sm" wire:model.defer="filters.estado">
                                                    <option value="">TODOS</option>
                                                    <option value="Recibido">RECIBIDO</option>
                                                    <option value="Entregado">ENTREGADO</option>
                                                    <option value="Devuelto">DEVUELTO</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grupo 2: Personas -->
                            <div class="col-md-12">
                                <div class="card mb-2">
                                    <div class="card-header bg-light py-1 px-2 border-bottom-0">
                                        <strong><small><i class="fas fa-users mr-1"></i> Información del Titular</small></strong>
                                    </div>
                                    <div class="card-body py-2 px-3">
                                        <div class="row">
                                            <div class="col-md-3 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="Cédula" wire:model.defer="filters.titular_cedula">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="Nombres" wire:model.defer="filters.titular_nombre">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="Apellidos" wire:model.defer="filters.titular_apellido">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <input type="text" class="form-control form-control-sm" placeholder="Nro. Tarjeta" wire:model.defer="filters.numero_tarjeta">
                                            </div>
                                        </div>

                                        <div class="text-right mt-2 pt-2 border-top">
                                            <button type="button" wire:click="resetFilters" class="btn btn-secondary btn-sm mr-2 px-3">
                                                <i class="fas fa-eraser mr-1"></i> Limpiar Filtros
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-sm px-5 font-weight-bold">
                                                <i class="fas fa-search mr-1"></i> BUSCAR
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados de la Búsqueda -->
    @if(!is_null($resultados))
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-1">
                    <h6 class="mb-0"><i class="fas fa-list mr-2"></i>Resultados ({{ count($resultados) }})</h6>
                    @if(count($resultados) > 0)
                    <button type="button" wire:click="imprimir" class="btn btn-light btn-sm font-weight-bold text-dark py-0 px-2" style="font-size: 0.8rem;">
                        <i class="fas fa-file-download mr-1"></i> Descargar PDF
                    </button>
                    @endif
                </div>
                <div class="card-body p-0 table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="thead-dark">
                            <tr>
                                <th class="sticky-top">Recibido</th>
                                <th class="sticky-top">Cédula</th>
                                <th class="sticky-top">Titular</th>
                                <th class="sticky-top">Nro. Tarjeta</th>
                                <th class="sticky-top">Estado</th>
                                <th class="sticky-top">Entregado</th>
                                <th class="sticky-top">Devuelto</th>
                                <th class="sticky-top">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($resultados as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row->fecha_recibido instanceof \Carbon\Carbon ? $row->fecha_recibido->format('d/m/Y') : \Carbon\Carbon::parse($row->fecha_recibido)->format('d/m/Y') }}</td>
                                <td>{{ $row->titular_cedula }}</td>
                                <td>{{ $row->titular_nombre }} {{ $row->titular_apellido }}</td>
                                <td class="text-nowrap">{{ $row->numero_tarjeta }}</td>
                                <td>
                                    <span class="badge badge-{{ $row->estado == 'Entregado' ? 'success' : ($row->estado == 'Devuelto' ? 'danger' : 'warning') }}">
                                        {{ $row->estado }}
                                    </span>
                                </td>
                                <td class="text-nowrap">{{ $row->fecha_entregado ? \Carbon\Carbon::parse($row->fecha_entregado)->format('d/m/Y') : '' }}</td>
                                <td class="text-nowrap">{{ $row->fecha_devuelto ? \Carbon\Carbon::parse($row->fecha_devuelto)->format('d/m/Y') : '' }}</td>
                                <td>{{ Str::limit($row->observaciones, 40) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i><br>
                                        No se encontraron resultados.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>