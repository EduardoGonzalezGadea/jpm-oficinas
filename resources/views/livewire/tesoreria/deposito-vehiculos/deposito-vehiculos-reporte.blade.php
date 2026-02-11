<div class="reporte-avanzado-container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-car mr-2"></i>Reporte Avanzado de Depósito de Vehículos</h5>
                    <a href="{{ route('tesoreria.deposito-vehiculos.index') }}" class="btn btn-sm btn-light text-dark font-weight-bold">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a la Vista Principal
                    </a>
                </div>
                <div class="card-body py-2">
                    <form wire:submit.prevent="buscar">
                        <div class="row">
                            <!-- Grupo 1: Cronología y Fechas (Ancho Total) -->
                            <div class="col-md-12">
                                <div class="card mb-2">
                                    <div class="card-header bg-light py-1 px-2 border-bottom-0">
                                        <strong><small><i class="fas fa-calendar-alt mr-1"></i> Período y Fechas del Recibo</small></strong>
                                    </div>
                                    <div class="card-body py-2 px-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="mb-0 small font-weight-bold text-primary">Rango de Fecha del Recibo</label>
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
                                            <div class="col-md-3">
                                                <label class="mb-0 small">Mes Referencia</label>
                                                <select class="form-control form-control-sm" wire:model.defer="filters.mes">
                                                    <option value="">Todos</option>
                                                    @foreach(range(1, 12) as $m)
                                                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->monthName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="mb-0 small">Año</label>
                                                <input type="number" class="form-control form-control-sm" wire:model.defer="filters.year">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Grupo 2: Datos del Depósito (Ancho Total) -->
                            <div class="col-md-12">
                                <div class="card mb-2">
                                    <div class="card-header bg-light py-1 px-2 border-bottom-0">
                                        <strong><small><i class="fas fa-info-circle mr-1"></i> Información del Titular e Identificación</small></strong>
                                    </div>
                                    <div class="card-body py-2 px-3">
                                        <div class="row">
                                            <!-- Titular y Documento -->
                                            <div class="col-md-6 border-right">
                                                <label class="mb-1 small font-weight-bold">Datos del Titular</label>
                                                <div class="form-row">
                                                    <div class="col-md-8 mb-2">
                                                        <input type="text" class="form-control form-control-sm" placeholder="Nombre completo del titular" wire:model.defer="filters.titular">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <input type="text" class="form-control form-control-sm" placeholder="Documento / RUT" wire:model.defer="filters.cedula">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Recibo y Orden -->
                                            <div class="col-md-6">
                                                <label class="mb-1 small font-weight-bold">Identificación del Pago</label>
                                                <div class="form-row">
                                                    <div class="col-md-2 mb-2">
                                                        <input type="text" class="form-control form-control-sm" placeholder="Serie" wire:model.defer="filters.recibo_serie">
                                                    </div>
                                                    <div class="col-md-5 mb-2">
                                                        <input type="text" class="form-control form-control-sm" placeholder="Nro. Recibo" wire:model.defer="filters.recibo_numero">
                                                    </div>
                                                    <div class="col-md-5 mb-2">
                                                        <input type="text" class="form-control form-control-sm" placeholder="Orden de Cobro" wire:model.defer="filters.orden_cobro">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-row border-top mt-2 pt-2">
                                            <div class="col-md-4 mb-1">
                                                <label class="mb-0 small">Medio de Pago</label>
                                                <select class="form-control form-control-sm" wire:model.defer="filters.medio_pago_id">
                                                    <option value="">TODOS</option>
                                                    @foreach($mediosPago as $medio)
                                                    <option value="{{ $medio->id }}">{{ $medio->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-1">
                                                <label class="mb-0 small">Rango de Monto</label>
                                                <div class="form-row">
                                                    <div class="col-6">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                            <input type="number" step="0.01" class="form-control" placeholder="Min" wire:model.defer="filters.monto_min">
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                                            <input type="number" step="0.01" class="form-control" placeholder="Max" wire:model.defer="filters.monto_max">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-right pt-4">
                                                <button type="button" wire:click="resetFilters" class="btn btn-secondary btn-sm mr-2 px-3">
                                                    <i class="fas fa-eraser mr-1"></i> Limpiar
                                                </button>
                                                <button type="submit" class="btn btn-primary btn-sm px-5 font-weight-bold">
                                                    <i class="fas fa-search mr-1"></i> BUSCAR
                                                </button>
                                            </div>
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
            <div class="card shadow-sm border-dark">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-1">
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
                                <th class="sticky-top">Fecha Recibo</th>
                                <th class="sticky-top">Serie/Nro</th>
                                <th class="sticky-top">Titular</th>
                                <th class="sticky-top">Documento</th>
                                <th class="sticky-top">Orden Cobro</th>
                                <th class="sticky-top">Medio Pago</th>
                                <th class="sticky-top text-right">Monto</th>
                                <th class="sticky-top">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($resultados as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row->recibo_fecha ? $row->recibo_fecha->format('d/m/Y') : 'N/A' }}</td>
                                <td class="text-nowrap">{{ $row->recibo_serie }}-{{ $row->recibo_numero }}</td>
                                <td>{{ $row->titular }}</td>
                                <td class="text-nowrap">{{ $row->cedula }}</td>
                                <td class="text-nowrap">{{ $row->orden_cobro }}</td>
                                <td>{{ $row->medioPago->nombre ?? 'N/A' }}</td>
                                <td class="text-right font-weight-bold text-dark">$ {{ number_format($row->monto, 2, ',', '.') }}</td>
                                <td>
                                    @if($row->planilla_id)
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Planillado #{{ $row->planilla_id }}</span>
                                    @else
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i><br>
                                        No se encontraron resultados para los filtros aplicados.
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