<div class="container-fluid px-0">
    @section('title', 'Reporte de Recibos - Tesorería')

    {{-- Header --}}
    <div class="card mb-2">
        <div class="card-header bg-danger text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-clipboard-list mr-2"></i>Reporte de Recibos para Contabilidad</strong>
                </h4>
                <span class="badge badge-light px-3 py-2">
                    <i class="fas fa-university mr-1"></i> Dirección de Tesorería
                </span>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-body py-2 px-3">
            <form wire:submit.prevent="generarReporte">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="small font-weight-bold mb-0">Fecha Desde</label>
                        <input type="date" wire:model.defer="fechaDesde" class="form-control form-control-sm">
                        @error('fechaDesde') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="small font-weight-bold mb-0">Fecha Hasta</label>
                        <input type="date" wire:model.defer="fechaHasta" class="form-control form-control-sm">
                        @error('fechaHasta') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                                <i class="fas fa-search mr-1" wire:loading.class="d-none" wire:target="generarReporte"></i>
                                <i class="fas fa-spinner fa-spin mr-1 d-none" wire:loading.class.remove="d-none" wire:target="generarReporte"></i>
                                Generar Reporte
                            </button>

                            @if($reporte)
                            <button type="button" class="btn btn-success btn-sm" wire:click="exportarExcel">
                                <i class="fas fa-file-excel mr-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-info btn-sm" onclick="window.open('{{ route('tesoreria.reporte-recibos.imprimir', ['desde' => $fechaDesde, 'hasta' => $fechaHasta]) }}', '_blank')">
                                <i class="fas fa-print mr-1"></i> Imprimir
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="toggleDetalle">
                                <i class="fas fa-{{ $mostrarDetalle ? 'compress-alt' : 'expand-alt' }} mr-1"></i>
                                {{ $mostrarDetalle ? 'Solo Resumen' : 'Ver Detalle' }}
                            </button>
                            @endif

                            <button type="button" class="btn btn-danger btn-sm" wire:click="limpiar">
                                <i class="fas fa-times mr-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Resultados --}}
    @if($reporte)
    @php
        $totalSecciones = count($reporte['secciones']);
        $todasActivas   = count($seccionesActivas) === $totalSecciones;
        $algunaActiva   = count($seccionesActivas) > 0 && !$todasActivas;
    @endphp
    <div class="card mb-2">
        <div class="card-header bg-dark text-white py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold">
                    <i class="fas fa-chart-bar mr-2"></i>Resumen del Período
                </h5>
                <span class="text-white-50">
                    {{ $reporte['fecha_desde'] }} al {{ $reporte['fecha_hasta'] }}
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Tabla Resumen con selectores --}}
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th class="align-middle text-center" style="width: 42px;">
                            {{-- Checkbox "Seleccionar Todas" — wire:click.prevent evita el doble toggle del navegador --}}
                            <div class="custom-control custom-checkbox mb-0" title="{{ $todasActivas ? 'Deseleccionar todas' : 'Seleccionar todas' }}">
                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="chk-todas"
                                    wire:click.prevent="toggleTodas"
                                    @if($todasActivas) checked @endif
                                    style="cursor: pointer;"
                                >
                                <label class="custom-control-label" for="chk-todas" style="cursor: pointer;"></label>
                            </div>
                        </th>
                        <th class="align-middle">Concepto</th>
                        <th class="align-middle text-center" style="width: 130px;">Cant. Recibos</th>
                        <th class="align-middle text-right" style="width: 160px;">Monto Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reporte['secciones'] as $index => $seccion)
                    @php $activa = in_array($index, $seccionesActivas); @endphp
                    <tr wire:key="resumen-seccion-{{ $index }}"
                        class="{{ !$activa ? 'text-muted bg-light' : ($seccion['cantidad'] > 0 ? '' : 'text-muted') }}"
                        style="{{ !$activa ? 'opacity: 0.55;' : '' }}">
                        <td class="align-middle text-center">
                            <div class="custom-control custom-checkbox mb-0">
                                {{-- wire:model con array es el binding idiomático de Livewire 2 para checkboxes --}}
                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="chk-seccion-{{ $index }}"
                                    wire:model="seccionesActivas"
                                    value="{{ $index }}"
                                    style="cursor: pointer;"
                                >
                                <label class="custom-control-label" for="chk-seccion-{{ $index }}" style="cursor: pointer;"></label>
                            </div>
                        </td>
                        <td class="align-middle font-weight-bold">
                            <i class="fas fa-folder{{ $seccion['cantidad'] > 0 && $activa ? '-open text-warning' : ' text-muted' }} mr-2"></i>
                            <span>{{ $seccion['nombre'] }}</span>
                            @if(!$activa)
                                <span class="badge badge-secondary ml-1 font-weight-normal" style="font-size: 0.7em;">excluido</span>
                            @endif
                        </td>
                        <td class="align-middle text-center">{{ $seccion['cantidad'] }}</td>
                        <td class="align-middle text-right font-weight-bold">{{ $seccion['monto_total_formateado'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-dark text-white font-weight-bold">
                        <td></td>
                        <td class="align-middle">
                            <i class="fas fa-calculator mr-2"></i>TOTAL GENERAL
                            @if(count($seccionesActivas) < $totalSecciones)
                                <small class="font-weight-normal ml-2 text-warning">
                                    ({{ count($seccionesActivas) }}/{{ $totalSecciones }} secciones)
                                </small>
                            @endif
                        </td>
                        <td class="align-middle text-center">{{ $totalesFiltrados['cantidad'] }}</td>
                        <td class="align-middle text-right">{{ $totalesFiltrados['monto_formateado'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Detalle por Sección --}}
    @if($mostrarDetalle)
    @foreach($reporte['secciones'] as $index => $seccion)
        @if($seccion['cantidad'] > 0 && in_array($index, $seccionesActivas))
        <div class="card mb-2">
            <div class="card-header py-1 px-3 d-flex justify-content-between align-items-center"
                 style="background-color: #f8f9fa; border-left: 4px solid #e74a3b;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-list mr-2 text-danger"></i>
                    {{ $seccion['nombre'] }}
                    <span class="badge badge-secondary ml-2">{{ $seccion['cantidad'] }} recibos</span>
                </h6>
                <span class="font-weight-bold text-danger">{{ $seccion['monto_total_formateado'] }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="align-middle" style="width: 120px;">Nro. Recibo</th>
                                <th class="align-middle" style="width: 100px;">Fecha</th>
                                <th class="align-middle" style="width: 130px;">Cédula</th>
                                <th class="align-middle">Titular</th>
                                <th class="align-middle text-right" style="width: 130px;">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seccion['registros'] as $registro)
                            <tr>
                                <td class="align-middle"><code>{{ $registro['recibo'] }}</code></td>
                                <td class="align-middle">{{ $registro['fecha'] }}</td>
                                <td class="align-middle">{{ $registro['cedula'] ?: '-' }}</td>
                                <td class="align-middle">{{ $registro['titular'] }}</td>
                                <td class="align-middle text-right">{{ $registro['monto_formateado'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-light font-weight-bold">
                                <td colspan="4" class="text-right align-middle">Subtotal {{ $seccion['nombre'] }}:</td>
                                <td class="text-right align-middle">{{ $seccion['monto_total_formateado'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif
    @endforeach

    {{-- Gran Total (filtrado) --}}
    <div class="card border-danger mb-3">
        <div class="card-body py-2 px-3 bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold">
                <i class="fas fa-calculator mr-2"></i>TOTAL GENERAL
                @if(count($seccionesActivas) < $totalSecciones)
                    <small class="font-weight-normal ml-2" style="font-size: 0.7em; opacity: 0.85;">
                        ({{ count($seccionesActivas) }} de {{ $totalSecciones }} secciones seleccionadas)
                    </small>
                @endif
            </h5>
            <div class="text-right">
                <span class="mr-4">{{ $totalesFiltrados['cantidad'] }} recibos</span>
                <span class="h4 mb-0 font-weight-bold">{{ $totalesFiltrados['monto_formateado'] }}</span>
            </div>
        </div>
    </div>
    @endif

    @else
    {{-- Estado vacío --}}
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-clipboard-list fa-4x text-muted mb-3 opacity-50"></i>
            <h5 class="text-muted">Seleccione un rango de fechas y presione "Generar Reporte"</h5>
            <p class="text-muted small">
                Por defecto se muestra el mes anterior:
                <strong>{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}</strong>
                al
                <strong>{{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</strong>
            </p>
        </div>
    </div>
    @endif
</div>
