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

            /* Ocultar elementos de navegación y estructurales del layout principal */
            .navbar,
            .main-sidebar,
            .main-footer,
            .content-header,
            .d-print-none {
                display: none !important;
            }

            /* Asegurar que el contenedor principal ocupe todo el ancho */
            .content-wrapper,
            body,
            html {
                margin: 0 !important;
                padding: 0 !important;
                background-color: white !important;
                width: 100% !important;
            }

            /* Estilos específicos para el reporte */
            .print-mode,
            .reporte-contenido {
                display: block !important;
                width: 100% !important;
                position: static !important;
                font-size: 8pt !important;
                line-height: 1.1 !important;
            }

            .print-mode table,
            .reporte-contenido table {
                width: 100% !important;
                max-width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                font-size: 7pt !important;
            }

            .print-mode th,
            .print-mode td,
            .reporte-contenido th,
            .reporte-contenido td {
                border: 1px solid black !important;
                padding: 2px 3px !important;
                color: black !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                white-space: normal !important;
                vertical-align: top !important;
            }

            .print-mode th,
            .reporte-contenido th {
                font-size: 7pt !important;
                font-weight: bold !important;
            }

            /* Columnas específicas - ajustar anchos */
            .print-mode table th:nth-child(1),
            .print-mode table td:nth-child(1) {
                width: 5% !important;
            }

            /* Serie */
            .print-mode table th:nth-child(2),
            .print-mode table td:nth-child(2) {
                width: 7% !important;
            }

            /* N° Cheque */
            .print-mode table th:nth-child(3),
            .print-mode table td:nth-child(3) {
                width: 6% !important;
            }

            /* Banco */
            .print-mode table th:nth-child(4),
            .print-mode table td:nth-child(4) {
                width: 8% !important;
            }

            /* Cuenta */
            .print-mode table th:nth-child(5),
            .print-mode table td:nth-child(5) {
                width: 6% !important;
            }

            /* Estado */
            .print-mode table th:nth-child(6),
            .print-mode table td:nth-child(6) {
                width: 8% !important;
            }

            /* F. Ingreso */
            .print-mode table th:nth-child(7),
            .print-mode table td:nth-child(7) {
                width: 8% !important;
            }

            /* F. Emisión */
            .print-mode table th:nth-child(8),
            .print-mode table td:nth-child(8) {
                width: 8% !important;
            }

            /* F. Anulación */
            .print-mode table th:nth-child(9),
            .print-mode table td:nth-child(9) {
                width: 10% !important;
            }

            /* Motivo Anulación */
            .print-mode table th:nth-child(10),
            .print-mode table td:nth-child(10) {
                width: 6% !important;
            }

            /* En Planilla */
            .print-mode table th:nth-child(11),
            .print-mode table td:nth-child(11) {
                width: 7% !important;
            }

            /* Monto */
            .print-mode table th:nth-child(12),
            .print-mode table td:nth-child(12) {
                width: 12% !important;
            }

            /* Beneficiario */
            .print-mode table th:nth-child(13),
            .print-mode table td:nth-child(13) {
                width: 9% !important;
            }

            /* Concepto */

            /* Ajustes de página */
            @page {
                size: A4 landscape;
                margin: 0.5cm;
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
                <h4 class="card-title p-0 m-0">
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
                                    <select wire:model="reporteMes" class="form-control">
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
                                    <select wire:model="reporteAnio" class="form-control">
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
                                    <select wire:model="filtroBanco" class="form-control">
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
                                    <select wire:model="filtroCuentaBancaria" class="form-control" @if(!$filtroBanco) disabled @endif>
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
                                    <select wire:model="filtroEstado" class="form-control">
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
                                    <select wire:model="filtroEnPlanilla" class="form-control">
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
                                    @if(in_array($reporteTipo, ['anulados_mes', 'emitidos_mes' , 'planillas_mes' , 'planillas_anuladas_mes' ]) && (!$reporteMes || !$reporteAnio))
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
                                @if($reporteTipo === 'listado_general')
                                <button wire:click="activarImpresion" class="btn btn-primary btn-block"
                                    @if(!$showReport) disabled @endif
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="activarImpresion">
                                        <i class="fas fa-print mr-1"></i>Imprimir
                                    </span>
                                    <span wire:loading wire:target="activarImpresion">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>Cargando datos...
                                    </span>
                                </button>
                                @else
                                <button onclick="printReport()" class="btn btn-primary btn-block" @if(!$showReport) disabled @endif>
                                    <i class="fas fa-print mr-1"></i>Imprimir
                                </button>
                                @endif
                            </div>
                            @if($reporteTipo === 'stock')
                            <div class="col-md-4 mt-2">
                                <button onclick="generatePDF()" class="btn btn-danger btn-block" @if(!$showReport) disabled @endif>
                                    <i class="fas fa-file-pdf mr-1"></i>Descargar PDF
                                </button>
                            </div>
                            @endif
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
                                                    <input type="date" wire:model="filtroFechaIngresoDesde" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label for="filtroFechaIngresoHasta">F. Ingreso Hasta</label>
                                                    <input type="date" wire:model="filtroFechaIngresoHasta" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label for="filtroFechaEmisionDesde">F. Emisión Desde</label>
                                                    <input type="date" wire:model="filtroFechaEmisionDesde" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label for="filtroFechaEmisionHasta">F. Emisión Hasta</label>
                                                    <input type="date" wire:model="filtroFechaEmisionHasta" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label for="filtroFechaAnulacionDesde">F. Anulación Desde</label>
                                                    <input type="date" wire:model="filtroFechaAnulacionDesde" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label for="filtroFechaAnulacionHasta">F. Anulación Hasta</label>
                                                    <input type="date" wire:model="filtroFechaAnulacionHasta" class="form-control">
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

    @if($reporteTipo === 'stock')
    <!-- Historial de Reportes (Acordeón) -->
    <div class="accordion mt-4 d-print-none" id="accordionHistorial" wire:poll.visible.10s="cargarHistorial">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0 flex-grow-1">
                    <button class="btn btn-link btn-block text-left" type="button" wire:click="toggleHistorial">
                        <i class="fas fa-history mr-2"></i>Historial de Reportes Guardados
                        @if($mostrarHistorial)
                        <i class="fas fa-chevron-up float-right"></i>
                        @else
                        <i class="fas fa-chevron-down float-right"></i>
                        @endif
                    </button>
                </h2>
            </div>

            <div id="collapseHistorial" class="collapse {{ $mostrarHistorial ? 'show' : '' }}">
                <div class="card-body">
                    @if(count($historialStock) > 0)
                    <div class="list-group">
                        @foreach($historialStock as $index => $archivo)
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" wire:key="historial-{{ $index }}">
                            <a href="{{ route('tesoreria.cheques.reportes.download-stock', $archivo['filename']) }}" target="_blank" class="text-dark text-decoration-none flex-grow-1">
                                <i class="fas fa-file-pdf text-danger mr-2"></i>
                                {{ \Carbon\Carbon::parse($archivo['date'])->format('d/m/Y H:i:s') }}
                                <small class="text-muted ml-2">({{ $archivo['filename'] }})</small>
                                <span class="badge badge-primary badge-pill ml-2">{{ $archivo['size'] }} KB</span>
                            </a>
                            <button class="btn btn-sm btn-outline-danger ml-2" onclick="confirmarEliminacionReporte('{{ $archivo['filename'] }}')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted text-center mb-0">No hay reportes guardados.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function printReport() {
            window.print();
        }

        function generatePDF() {
            // Seleccionar el contenedor del reporte
            const element = document.querySelector('.reporte-contenido');
            if (!element) {
                console.error('Elemento .reporte-contenido no encontrado');
                return;
            }

            // Mostrar indicador de carga
            Swal.fire({
                title: 'Generando PDF...',
                text: 'Por favor espere mientras se prepara el documento.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Crear un contenedor temporal para incluir el encabezado que no está en .reporte-contenido
            setTimeout(() => {
                const container = document.createElement('div');
                container.innerHTML = `
                <div class="print-header" style="text-align: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; font-size: 18pt;">JEFATURA DE POLICÍA DE MONTEVIDEO</h2>
                    <h3 style="margin: 5px 0; font-size: 16pt;">DIRECCIÓN DE TESORERÍA</h3>
                    <h4 style="margin-top: 15px; font-size: 14pt;"><strong>STOCK DE CHEQUES AL {{ now()->format('d/m/Y') }}</strong></h4>
                </div>
            `;
                // Clonar el contenido para no afectar la vista actual
                container.appendChild(element.cloneNode(true));

                // Ajustar estilos para el PDF en el clon
                const tables = container.querySelectorAll('table');
                tables.forEach(table => {
                    table.style.width = '100%';
                    table.style.borderCollapse = 'collapse';
                    table.style.marginBottom = '10px';
                    table.style.fontSize = '10pt';
                });

                const cells = container.querySelectorAll('th, td');
                cells.forEach(cell => {
                    cell.style.border = '1px solid black';
                    cell.style.padding = '4px';
                });

                // Configuración optimizada para html2pdf (basada en Valores pero landscape)
                const opt = {
                    margin: 10,
                    filename: 'stock_cheques_{{ now()->format("Y-m-d_H-i-s") }}.pdf',
                    image: {
                        type: 'jpeg',
                        quality: 0.98
                    },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        logging: true,
                        scrollY: 0,
                        windowWidth: document.documentElement.offsetWidth
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'landscape'
                    },
                    pagebreak: {
                        mode: ['avoid-all', 'css', 'legacy']
                    }
                };

                // Generar PDF
                html2pdf().set(opt).from(container).toPdf().get('pdf').then(function(pdf) {
                    // Descargar localmente
                    pdf.save(opt.filename);

                    // Obtener blob para subir
                    const blob = pdf.output('blob');
                    uploadPdf(blob);

                }).catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un error al generar el PDF.'
                    });
                });
            }, 100);
        }

        function uploadPdf(blob) {
            const formData = new FormData();
            formData.append('pdf', blob, 'reporte.pdf');

            // No mostramos otro Swal de carga aquí porque ya viene del generatePDF
            // Solo actualizamos el estado si fuera necesario, pero fetch es rápido.

            fetch('{{ route("tesoreria.cheques.reportes.upload-stock") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Emitir evento a Livewire para actualizar la lista
                        Livewire.emit('stockGenerado');

                        // Mostrar éxito como Toast (menos intrusivo)
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        });

                        Toast.fire({
                            icon: 'success',
                            title: 'Reporte guardado en el historial'
                        });
                    } else {
                        throw new Error('Error en la respuesta del servidor');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo guardar el reporte en el historial.'
                    });
                });
        }

        // Listener para eventos SweetAlert desde Livewire
        window.addEventListener('swal', event => {
            const data = event.detail;

            if (data.toast) {
                // Mostrar como toast
                const Toast = Swal.mixin({
                    toast: true,
                    position: data.position || 'top-end',
                    showConfirmButton: data.showConfirmButton !== undefined ? data.showConfirmButton : false,
                    timer: data.timer || 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                Toast.fire({
                    icon: data.icon,
                    title: data.title,
                    text: data.text
                });
            } else {
                // Mostrar como alerta normal
                Swal.fire({
                    icon: data.icon,
                    title: data.title,
                    text: data.text,
                    confirmButtonText: data.confirmButtonText || 'OK'
                });
            }
        });

        function confirmarEliminacionReporte(filename) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "No podrás revertir esta acción. El archivo será eliminado permanentemente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.eliminarReporte(filename);
                }
            })
        }
        // Escuchar evento de impresión
        window.addEventListener('printNow', event => {
            console.log('printNow event received, waiting for data to load...');
            // Esperar a que se carguen los datos
            setTimeout(() => {
                console.log('Opening print dialog...');
                window.print();
                // Restaurar paginación después de imprimir
                setTimeout(() => {
                    @this.call('cancelarImpresion');
                    console.log('Print mode cancelled');
                }, 1000);
            }, 1000);
        });
    </script>
</div>
