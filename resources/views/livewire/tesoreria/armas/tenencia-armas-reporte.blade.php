<div class="reporte-avanzado-container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-print mr-2"></i>Reporte Avanzado de Tenencia de Armas</h5>
                    <a href="{{ route('tesoreria.armas.tenencia') }}" class="btn btn-sm btn-light text-dark font-weight-bold">
                        <i class="fas fa-arrow-left mr-1"></i> Volver a la Vista Principal
                    </a>
                </div>
                <div class="card-body py-2">
                    <form wire:submit.prevent="buscar">
                        <div class="row">
                            <!-- Fechas -->
                            <div class="col-md-5">
                                <div class="card mb-2">
                                    <div class="card-header bg-light py-1 px-2 border-bottom-0"><strong><small>Filtros de Fecha</small></strong></div>
                                    <div class="card-body py-1 px-2">
                                        <div class="form-row">
                                            <div class="col-md-6 mb-2">
                                                <label class="mb-0 small" for="fecha_desde">Fecha Desde</label>
                                                <input type="date" id="fecha_desde" class="form-control form-control-sm" wire:model.defer="filters.fecha_desde">
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="mb-0 small" for="fecha_hasta">Fecha Hasta</label>
                                                <input type="date" id="fecha_hasta" class="form-control form-control-sm" wire:model.defer="filters.fecha_hasta">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-md-6 mb-2">
                                                <label class="mb-0 small" for="mes">Mes</label>
                                                <select id="mes" class="form-control form-control-sm" wire:model.defer="filters.mes">
                                                    <option value="">Todos</option>
                                                    <option value="1">Enero</option>
                                                    <option value="2">Febrero</option>
                                                    <option value="3">Marzo</option>
                                                    <option value="4">Abril</option>
                                                    <option value="5">Mayo</option>
                                                    <option value="6">Junio</option>
                                                    <option value="7">Julio</option>
                                                    <option value="8">Agosto</option>
                                                    <option value="9">Septiembre</option>
                                                    <option value="10">Octubre</option>
                                                    <option value="11">Noviembre</option>
                                                    <option value="12">Diciembre</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="mb-0 small" for="year">Año</label>
                                                <input type="number" id="year" class="form-control form-control-sm" wire:model.defer="filters.year">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Otros filtros -->
                            <div class="col-md-7">
                                <div class="card mb-2">
                                    <div class="card-header bg-light py-1 px-2 border-bottom-0"><strong><small>Titular y Documentación</small></strong></div>
                                    <div class="card-body py-1 px-2">
                                        <div class="form-row">
                                            <div class="col-md-8 mb-2">
                                                <label class="mb-0 small" for="titular">Nombre / Titular</label>
                                                <input type="text" id="titular" class="form-control form-control-sm" wire:model.defer="filters.titular" placeholder="Nombre completo o parcial">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="mb-0 small" for="cedula">Cédula</label>
                                                <input type="text" id="cedula" class="form-control form-control-sm" wire:model.defer="filters.cedula" placeholder="1.234.567-8">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="col-md-4 mb-2">
                                                <label class="mb-0 small" for="numero_tramite">Nro. Trámite</label>
                                                <input type="text" id="numero_tramite" class="form-control form-control-sm" wire:model.defer="filters.numero_tramite">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="mb-0 small" for="recibo">Recibo</label>
                                                <input type="text" id="recibo" class="form-control form-control-sm" wire:model.defer="filters.recibo">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="mb-0 small" for="orden_cobro">Ord. Cobro</label>
                                                <input type="text" id="orden_cobro" class="form-control form-control-sm" wire:model.defer="filters.orden_cobro">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="button" wire:click="resetFilters" class="btn btn-secondary btn-sm mr-2">
                                    <i class="fas fa-eraser mr-1"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm px-4 font-weight-bold">
                                    <i class="fas fa-search mr-1"></i> BUSCAR
                                </button>
                                <div wire:loading wire:target="buscar" class="ml-2 small text-primary">
                                    <i class="fas fa-spinner fa-spin"></i> Buscando...
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
                                <th class="sticky-top">Fecha</th>
                                <th class="sticky-top">Titular</th>
                                <th class="sticky-top">Cédula</th>
                                <th class="sticky-top">Nro. Trámite</th>
                                <th class="sticky-top">Recibo / OC</th>
                                <th class="sticky-top text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($resultados as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row->fecha instanceof \Carbon\Carbon ? $row->fecha->format('d/m/Y') : \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $row->titular }}</td>
                                <td class="text-nowrap">{{ $row->cedula }}</td>
                                <td class="text-nowrap">{{ $row->numero_tramite }}</td>
                                <td class="text-nowrap">{{ $row->recibo }} / {{ $row->orden_cobro }}</td>
                                <td class="text-right text-nowrap">$ {{ number_format($row->monto, 2, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i><br>
                                        No se encontraron resultados.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($resultados) > 0)
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold">TOTAL:</td>
                                <td class="text-right font-weight-bold text-nowrap">$ {{ number_format($resultados->sum('monto'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>