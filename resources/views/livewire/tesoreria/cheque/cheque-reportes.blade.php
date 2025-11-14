<div class="@if($printMode) print-mode @endif">
    @if($printMode)
        <div class="print-header">
            <h2>JEFATURA DE POLICÍA DE MONTEVIDEO</h2>
            <h3>DIRECCIÓN DE TESORERÍA</h3>
            @if(!empty($reportTitle))
                <h4 style="margin-top: 15px;"><strong>{{ $reportTitle }} AL {{ now()->format('d/m/Y') }}</strong></h4>
            @endif
        </div>
    @endif

<style>
    @media print {
        /* Ocultar todo por defecto */
        body * {
            visibility: hidden;
        }

        /* Hacer visible solo el contenedor del reporte y su contenido */
        .print-mode, .print-mode * {
            visibility: visible;
        }

        /* Sacar el contenido del flujo normal y posicionarlo en la parte superior */
        .print-mode {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .print-mode .reporte-contenido {
            font-size: 10pt;
            line-height: 1.2;
        }

        .print-mode .d-print-none {
            display: none !important;
        }

        .print-mode .container-fluid {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .print-mode svg {
            width: 1em;
            height: 1em;
        }

        .print-mode table, .print-mode th, .print-mode td {
            border: 1px solid black !important;
            border-collapse: collapse !important;
        }

        .print-mode th, .print-mode td {
            padding: 3px !important;
        }

        .no-print-actions {
            display: none !important;
        }

        .print-avoid-break {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        @page {
            size: A4 landscape;
            margin: 0.7cm;
        }
    }

    .print-header {
        text-align: center;
        margin-bottom: 15px;
    }
</style>
    <!-- Elementos visibles solo en pantalla, ocultos en impresión -->
    <div class="d-print-none">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="fas fa-chart-bar mr-2"></i>Reportes de Cheques
                </h4>
            </div>
            <div class="card-body">
                <!-- Selector de tipo de reporte -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
                            <label class="btn btn-outline-primary {{ $reporteTipo === 'stock' ? 'active' : '' }} mb-1 mr-1">
                                <input type="radio" wire:click="cambiarReporte('stock')" {{ $reporteTipo === 'stock' ? 'checked' : '' }}> Stock de Cheques
                            </label>
                            <label class="btn btn-outline-warning {{ $reporteTipo === 'anulados_mes' ? 'active' : '' }} mb-1 mr-1">
                                <input type="radio" wire:click="cambiarReporte('anulados_mes')" {{ $reporteTipo === 'anulados_mes' ? 'checked' : '' }}> Cheques Anulados por Mes
                            </label>
                            <label class="btn btn-outline-success {{ $reporteTipo === 'emitidos_mes' ? 'active' : '' }} mb-1 mr-1">
                                <input type="radio" wire:click="cambiarReporte('emitidos_mes')" {{ $reporteTipo === 'emitidos_mes' ? 'checked' : '' }}> Cheques Emitidos por Mes
                            </label>
                            <label class="btn btn-outline-info {{ $reporteTipo === 'planillas_mes' ? 'active' : '' }} mb-1 mr-1">
                                <input type="radio" wire:click="cambiarReporte('planillas_mes')" {{ $reporteTipo === 'planillas_mes' ? 'checked' : '' }}> Planillas Emitidas por Mes
                            </label>
                            <label class="btn btn-outline-danger {{ $reporteTipo === 'planillas_anuladas_mes' ? 'active' : '' }} mb-1 mr-1">
                                <input type="radio" wire:click="cambiarReporte('planillas_anuladas_mes')" {{ $reporteTipo === 'planillas_anuladas_mes' ? 'checked' : '' }}> Planillas Anuladas por Mes
                            </label>
                            <label class="btn btn-outline-primary {{ $reporteTipo === 'listado_general' ? 'active' : '' }} mb-1 mr-1">
                                <input type="radio" wire:click="cambiarReporte('listado_general')" {{ $reporteTipo === 'listado_general' ? 'checked' : '' }}> Listado General
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Filtros y botón para generar reporte -->
                @if($reporteTipo)
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="row">
                                <!-- Controles de fecha para reportes mensuales -->
                                @if(in_array($reporteTipo, ['anulados_mes', 'emitidos_mes', 'planillas_mes', 'planillas_anuladas_mes']))
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="reporteMes">Mes</label>
                                            <select wire:model.live="reporteMes" class="form-control">
                                                <option value="">Seleccionar mes...</option>
                                                @for($i = 1; $i <= 12; $i++)
                                                    <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->locale('es')->monthName }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="reporteAnio">Año</label>
                                            <select wire:model.live="reporteAnio" class="form-control">
                                                <option value="">Seleccionar año...</option>
                                                @for($year = date('Y'); $year >= date('Y') - 10; $year--)
                                                    <option value="{{ $year }}">{{ $year }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                @endif

                                <!-- Filtros para listado general -->
                                @if($reporteTipo === 'listado_general')
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroBanco">Banco</label>
                                            <select wire:model.live="filtroBanco" class="form-control">
                                                <option value="">Todos los bancos</option>
                                                @foreach($bancos as $banco)
                                                    <option value="{{ $banco->id }}">{{ $banco->codigo }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroCuentaBancaria">Cuenta Bancaria</label>
                                            <select wire:model.live="filtroCuentaBancaria" class="form-control" @if(!$filtroBanco) disabled @endif>
                                                <option value="">Todas las cuentas</option>
                                                @foreach($cuentasBancarias as $cuenta)
                                                    <option value="{{ $cuenta->id }}">{{ $cuenta->numero_cuenta }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroEstado">Estado</label>
                                            <select wire:model.live="filtroEstado" class="form-control">
                                                <option value="">Todos los estados</option>
                                                <option value="disponible">Disponible</option>
                                                <option value="emitido">Emitido</option>
                                                <option value="anulado">Anulado</option>
                                                <option value="en_planilla">En Planilla</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="filtroEnPlanilla">En Planilla</label>
                                            <select wire:model.live="filtroEnPlanilla" class="form-control">
                                                <option value="">Todos</option>
                                                <option value="si">Sí</option>
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Botones de Acción -->
                            <div class="row">
                                <div class="col-md-4 mt-2">
                                    <button wire:click="generarReporte" class="btn btn-success btn-block"
                                        @if(in_array($reporteTipo, ['anulados_mes', 'emitidos_mes', 'planillas_mes', 'planillas_anuladas_mes']) && (!$reporteMes || !$reporteAnio))
                                            disabled
                                        @endif
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="generarReporte">
                                            <i class="fas fa-play-circle mr-1"></i>Generar Reporte
                                        </span>
                                        <span wire:loading wire:target="generarReporte">
                                            <i class="fas fa-spinner fa-spin mr-1"></i>Generando...
                                        </span>
                                    </button>
                                </div>
                                <div class="col-md-4 mt-2">
                                    <button onclick="printReport()" class="btn btn-primary btn-block" @if(!$showReport) disabled @endif>
                                        <i class="fas fa-print mr-1"></i>Imprimir
                                    </button>
                                </div>
                                @if($reporteTipo === 'listado_general')
                                <div class="col-md-4 mt-2">
                                    <button wire:click="limpiarFiltros" class="btn btn-secondary btn-block">
                                        <i class="fas fa-eraser mr-1"></i>Limpiar Filtros
                                    </button>
                                </div>
                                @endif
                            </div>

                            @if($reporteTipo === 'listado_general')
                                <!-- Filtros de fecha para listado general -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header py-2">
                                                <h6 class="card-title mb-0">
                                                    <i class="fas fa-calendar-alt mr-1"></i>Filtros de Fecha
                                                </h6>
                                            </div>
                                            <div class="card-body py-2">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-0">
                                                            <label for="filtroFechaIngresoDesde">F. Ingreso Desde</label>
                                                            <input type="date" wire:model.live="filtroFechaIngresoDesde" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-0">
                                                            <label for="filtroFechaIngresoHasta">F. Ingreso Hasta</label>
                                                            <input type="date" wire:model.live="filtroFechaIngresoHasta" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-0">
                                                            <label for="filtroFechaEmisionDesde">F. Emisión Desde</label>
                                                            <input type="date" wire:model.live="filtroFechaEmisionDesde" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-0">
                                                            <label for="filtroFechaEmisionHasta">F. Emisión Hasta</label>
                                                            <input type="date" wire:model.live="filtroFechaEmisionHasta" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-0">
                                                            <label for="filtroFechaAnulacionDesde">F. Anulación Desde</label>
                                                            <input type="date" wire:model.live="filtroFechaAnulacionDesde" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group mb-0">
                                                            <label for="filtroFechaAnulacionHasta">F. Anulación Hasta</label>
                                                            <input type="date" wire:model.live="filtroFechaAnulacionHasta" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Loader y contenido del reporte -->
    <div wire:loading wire:target="generarReporte" class="text-center py-5">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="sr-only">Cargando...</span>
        </div>
        <h4 class="mt-3"><strong>Procesando datos del reporte...</strong></h4>
        <p>Esto puede tardar unos segundos.</p>
    </div>

    <div wire:loading.remove wire:target="generarReporte">
        @if($showReport)
            <div class="reporte-contenido">
                @if($reporteTipo === 'stock' && isset($stockCheques))
                    @include('livewire.tesoreria.cheque.reportes.stock')
                @elseif($reporteTipo === 'anulados_mes' && isset($chequesAnuladosMes))
                    @include('livewire.tesoreria.cheque.reportes.anulados-mes')
                @elseif($reporteTipo === 'emitidos_mes' && isset($chequesEmitidosMes))
                    @include('livewire.tesoreria.cheque.reportes.emitidos-mes')
                @elseif($reporteTipo === 'planillas_mes' && isset($planillasEmitidasMes))
                    @include('livewire.tesoreria.cheque.reportes.planillas-mes')
                @elseif($reporteTipo === 'planillas_anuladas_mes' && isset($planillasAnuladasMes))
                    @include('livewire.tesoreria.cheque.reportes.planillas-anuladas-mes')
                @elseif($reporteTipo === 'listado_general' && isset($chequesFiltrados))
                    @include('livewire.tesoreria.cheque.reportes.listado-general')
                @endif
            </div>
        @else
            <div class="text-center py-5 d-print-none">
                <i class="fas fa-info-circle fa-3x text-muted"></i>
                <h4 class="mt-3">Seleccione un tipo de reporte y haga clic en "Generar Reporte".</h4>
            </div>
        @endif
    </div>
</div>
