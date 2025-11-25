<div class="container-fluid">
    <style>
        @media print {
            /* Ocultar todo por defecto */
            body * {
                visibility: hidden;
            }
    
            /* Hacer visible solo el contenedor de stock y su contenido */
            #stock-valores-print, #stock-valores-print * {
                visibility: visible;
            }
    
            /* Posicionar en la parte superior */
            #stock-valores-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
    
            /* Ocultar elementos con clase d-print-none */
            .d-print-none {
                display: none !important;
            }
    
            /* Ajustar tabla para impresión */
            #stock-valores-print table, 
            #stock-valores-print th, 
            #stock-valores-print td {
                border: 1px solid black !important;
                border-collapse: collapse !important;
            }
    
            #stock-valores-print th, 
            #stock-valores-print td {
                padding: 5px !important;
                font-size: 10pt;
            }
    
            /* Configuración de página */
            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }
        }

        /* Estilos para PDF - aplicar el mismo tamaño que en impresión */
        #stock-valores-print table th,
        #stock-valores-print table td {
            font-size: 10pt !important;
        }

        #stock-valores-print h2 {
            font-size: 14pt !important;
        }

        #stock-valores-print h3 {
            font-size: 12pt !important;
        }

        #stock-valores-print h4 {
            font-size: 11pt !important;
        }

        /* Evitar salto de página después del encabezado */
        .print-header {
            /* page-break-after: avoid !important;  <-- REMOVIDO: Causaba saltos inesperados */
            margin-bottom: 30px !important;
        }

        /* Permitir que la tabla se divida naturalmente */
        #stock-valores-print .table-responsive {
            page-break-inside: auto !important;
        }

        #stock-valores-print table {
            page-break-inside: auto !important;
            page-break-before: auto !important; /* Asegurar que no fuerce salto antes */
            margin-top: 0 !important;
        }

        #stock-valores-print thead {
            display: table-header-group !important;
        }

        #stock-valores-print tr {
            page-break-inside: avoid !important;
        }
    
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            display: none;
        }
    
        @media print {
            .print-header {
                display: block !important;
            }
        }
    </style>

    <div class="alert alert-info d-none">
        Debug: Última actualización vista: {{ now()->format('H:i:s') }}
    </div>

    <div class="row">
        <div class="col-12">

        </div>
        <div class="card-body">
            <!-- Navegación de pestañas Bootstrap nativo -->
            <ul class="nav nav-tabs" id="reportesTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $reporteTipo === 'stock' ? 'active' : '' }}" 
                       id="stock-tab" 
                       wire:click.prevent="cambiarReporte('stock')" 
                       href="#" 
                       role="tab">
                        <i class="fas fa-warehouse mr-1"></i>Stock de Valores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $reporteTipo === 'completas' ? 'active' : '' }}" 
                       id="completas-tab" 
                       wire:click.prevent="cambiarReporte('completas')" 
                       href="#" 
                       role="tab">
                        <i class="fas fa-box mr-1"></i>Libretas Completas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $reporteTipo === 'en_uso' ? 'active' : '' }}" 
                       id="en-uso-tab" 
                       wire:click.prevent="cambiarReporte('en_uso')" 
                       href="#" 
                       role="tab">
                        <i class="fas fa-handshake mr-1"></i>Libretas en Uso
                    </a>
                </li>
            </ul>

            <!-- Contenido de las pestañas -->
            <div class="tab-content mt-3" id="reportesTabContent">
                <!-- TAB: Stock de Valores -->
                <div class="tab-pane fade {{ $reporteTipo === 'stock' ? 'show active' : '' }}" id="stock" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
                        <h4 class="mb-0">Stock de Valores</h4>
                        <div>
                            <button onclick="descargarPDFStock()" class="btn btn-danger mr-2">
                                <i class="fas fa-file-pdf mr-1"></i>Descargar PDF
                            </button>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print mr-1"></i>Imprimir
                            </button>
                            <a href="{{ route('tesoreria.valores.index') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left mr-1"></i>Regresar a Valores
                            </a>
                        </div>
                    </div>

                    <div id="stock-valores-print">
                        <div class="print-header">
                            <h2>JEFATURA DE POLICÍA DE MONTEVIDEO</h2>
                            <h3>DIRECCIÓN DE TESORERÍA</h3>
                            <h4 style="margin-top: 15px;"><strong>STOCK DE VALORES AL {{ now()->format('d/m/Y') }}</strong></h4>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                <tr>
                                    <th>Concepto</th>
                                    <th class="text-center">Valor</th>
                                    <th class="text-center">Serie</th>
                                    <th class="text-center">Del N°</th>
                                    <th class="text-center">Al N°</th>
                                    <th class="text-center">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Libretas Completas -->
                                <tr class="table-secondary">
                                    <td colspan="6"><strong>Libretas Completas</strong></td>
                                </tr>
                                @forelse($stockData['completas'] as $index => $item)
                                    <tr wire:key="stock-completa-{{ $index }}">
                                        <td>{{ $item['concepto'] }}</td>
                                        <td class="text-center">{{ $item['valor'] }}</td>
                                        <td class="text-center">{{ $item['serie'] }}</td>
                                        <td class="text-center">{{ $item['del_numero'] }}</td>
                                        <td class="text-center">{{ $item['al_numero'] }}</td>
                                        <td class="text-center">{{ number_format($item['cantidad'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay libretas completas</td>
                                    </tr>
                                @endforelse
                                
                                <!-- Libretas En Uso -->
                                <tr class="table-secondary">
                                    <td colspan="6"><strong>Libretas En Uso</strong></td>
                                </tr>
                                @forelse($stockData['en_uso'] as $index => $item)
                                    <tr wire:key="stock-uso-{{ $index }}-{{ $item['del_numero'] }}">
                                        <td>{{ $item['concepto'] }}</td>
                                        <td class="text-center">{{ $item['valor'] }}</td>
                                        <td class="text-center">{{ $item['serie'] }}</td>
                                        <td class="text-center">{{ $item['del_numero'] }}</td>
                                        <td class="text-center">{{ $item['al_numero'] }}</td>
                                        <td class="text-center">{{ number_format($item['cantidad'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay libretas en uso</td>
                                    </tr>
                                @endforelse
                                
                                <!-- Total -->
                                <tr class="table-info font-weight-bold">
                                    <td colspan="5" class="text-right">Total de Recibos Disponibles:</td>
                                    <td class="text-center">{{ number_format($stockData['total_recibos'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

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
                                                <a href="{{ route('tesoreria.valores.reportes.download-stock', $archivo['filename']) }}" target="_blank" class="text-dark text-decoration-none flex-grow-1">
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
            </div>

            <!-- TAB: Libretas Completas -->
            <div class="tab-pane fade {{ $reporteTipo === 'completas' ? 'show active' : '' }}" id="completas" role="tabpanel">
                <!-- Filtros -->
                <div class="d-flex justify-content-between mb-3">
                    <div class="flex-fill mr-2">
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar...">
                    </div>
                    <div class="flex-fill mr-2">
                        <select wire:model.live="filtroTipoLibreta" class="form-control">
                            <option value="">Todos los tipos</option>
                            @foreach($tiposLibreta as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button wire:click="limpiarFiltros" class="btn btn-outline-danger" title="Limpiar Filtros">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>Tipo</th>
                                <th>Serie</th>
                                <th>Del N°</th>
                                <th>Al N°</th>
                                <th class="text-center">Cantidad</th>
                                <th>Fecha Recepción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($libretasCompletas as $index => $libreta)
                                <tr wire:key="completa-{{ $index }}">
                                    <td>{{ $libreta->tipoLibreta->nombre }}</td>
                                    <td>{{ $libreta->serie ?? 'S/N' }}</td>
                                    <td>{{ $libreta->numero_inicial }}</td>
                                    <td>{{ $libreta->numero_final }}</td>
                                    <td class="text-center">{{ number_format($libreta->total_recibos, 0, ',', '.') }}</td>
                                    <td>{{ $libreta->fecha_recepcion_inicial }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No hay libretas completas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">
                    {{ $libretasCompletas->links() }}
                </div>
            </div>

            <!-- TAB: Libretas en Uso -->
            <div class="tab-pane fade {{ $reporteTipo === 'en_uso' ? 'show active' : '' }}" id="en-uso" role="tabpanel">
                <!-- Filtros -->
                <div class="d-flex justify-content-between mb-3">
                    <div class="flex-fill mr-2">
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar...">
                    </div>
                    <div class="flex-fill mr-2">
                        <select wire:model.live="filtroTipoLibreta" class="form-control">
                            <option value="">Todos los tipos</option>
                            @foreach($tiposLibreta as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-fill mr-2">
                        <select wire:model.live="filtroServicio" class="form-control">
                            <option value="">Todos los servicios</option>
                            @foreach($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button wire:click="limpiarFiltros" class="btn btn-outline-danger" title="Limpiar Filtros">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th class="align-middle">Tipo</th>
                                <th class="align-middle">Servicio</th>
                                <th class="align-middle">Serie</th>
                                <th class="align-middle">Valor</th>
                                <th class="align-middle">Próximo Recibo</th>
                                <th class="align-middle">N° Final</th>
                                <th class="text-center align-middle">Recibos Disponibles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entregas as $entrega)
                                <tr wire:key="entrega-{{ $entrega->id }}-{{ $entrega->libretaValor->proximo_recibo_disponible }}">
                                    <td class="align-middle">{{ $entrega->libretaValor->tipoLibreta->nombre }}</td>
                                    <td class="align-middle">{{ $entrega->servicio->nombre }}</td>
                                    <td class="align-middle">{{ $entrega->libretaValor->serie }}</td>
                                    <td class="align-middle">{{ $entrega->servicio && $entrega->servicio->valor_ui ? number_format($entrega->servicio->valor_ui, 2, ',', '.') . ' U.I.' : 'S.V.E.' }}</td>
                                    <td class="align-middle">
                                        <input type="number" 
                                               wire:change="actualizarProximoRecibo({{ $entrega->libretaValor->id }}, $event.target.value)"
                                               value="{{ $entrega->libretaValor->proximo_recibo_disponible }}"
                                               class="form-control form-control-sm" 
                                               min="0"
                                               max="{{ $entrega->libretaValor->numero_final }}">
                                    </td>
                                    <td class="align-middle">{{ $entrega->libretaValor->numero_final }}</td>
                                    <td class="text-center align-middle">
                                        {{ number_format($entrega->libretaValor->numero_final - $entrega->libretaValor->proximo_recibo_disponible + 1, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No hay libretas en uso.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">
                    {{ $entregas->links() }}
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Script para generación de PDF de Stock (Cargado siempre) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function descargarPDFStock() {
            const element = document.getElementById('stock-valores-print');
            if (!element) {
                console.error('Elemento stock-valores-print no encontrado');
                return;
            }
    
            // Mostrar indicador de carga
            if (window.Swal) {
                Swal.fire({
                    title: 'Generando PDF...',
                    text: 'Por favor espere, esto puede tomar unos segundos.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }
    
            const filename = 'stock_valores_{{ now()->format("Y-m-d_H-i-s") }}.pdf';
            
            // Configuración simplificada para html2pdf
            const opt = {
                margin:       0.5,
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true, 
                    logging: true,
                    scrollY: 0
                },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['css', 'legacy'] }, /* Modo mixto para mejor compatibilidad */
                enableLinks:  false /* Optimización */
            };
    
            // Hacer visible temporalmente
            const originalDisplay = element.style.display;
            element.style.display = 'block';
            
            // Forzar visibilidad del encabezado
            const header = element.querySelector('.print-header');
            const originalHeaderDisplay = header ? header.style.display : '';
            if (header) header.style.display = 'block';

            // Remover table-responsive temporalmente
            const tableResponsive = element.querySelector('.table-responsive');
            let originalParent = null;
            let originalNextSibling = null;
            
            if (tableResponsive) {
                const table = tableResponsive.querySelector('table');
                if (table) {
                    originalParent = tableResponsive.parentNode;
                    originalNextSibling = tableResponsive.nextSibling;
                    // Mover tabla fuera del responsive
                    tableResponsive.parentNode.insertBefore(table, tableResponsive);
                    // Ocultar responsive (no remover para no perder referencia si hay algo más)
                    tableResponsive.style.display = 'none';
                }
            }
    
            // Pequeño delay para asegurar que el navegador renderice el contenido oculto
            setTimeout(() => {
                // Generar PDF
                html2pdf().set(opt).from(element).toPdf().get('pdf').then(function(pdf) {
                    // Restaurar estilos y estructura
                    element.style.display = originalDisplay;
                    if (header) header.style.display = originalHeaderDisplay;
                    
                    if (tableResponsive && originalParent) {
                        const table = element.querySelector('table'); // La tabla que movimos
                        if (table) {
                            tableResponsive.appendChild(table);
                            tableResponsive.style.display = '';
                        }
                    }
    
                    // Descargar localmente
                    pdf.save(filename);
                    
                    // Obtener blob para subir
                    const blob = pdf.output('blob');
                    const formData = new FormData();
                    formData.append('pdf', blob, filename);
                    formData.append('_token', '{{ csrf_token() }}');
    
                    // Subir al servidor
                    console.log('Iniciando subida al servidor...');
                    fetch('{{ route("tesoreria.valores.reportes.upload-stock") }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Respuesta del servidor:', data);
                        if(data.success) {
                            // Recargar historial en Livewire
                            if (window.Livewire) {
                                setTimeout(() => {
                                    window.Livewire.emit('stockGenerado');
                                    console.log('Evento stockGenerado emitido');
                                }, 1000);
                            }
                            
                            // Notificación con SweetAlert (Toast)
                            if (window.Swal) {
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
                            }
                        } else {
                            console.error('El servidor devolvió success: false');
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'No se pudo guardar el reporte en el historial.',
                                    confirmButtonText: 'Cerrar'
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error al subir:', error);
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Conexión',
                                                            text: 'Ocurrió un error al intentar guardar el reporte en el servidor.',
                                confirmButtonText: 'Cerrar'
                            });
                        }
                    });
                }).catch(error => {
                    console.error('Error al generar PDF:', error);
                    // Restaurar estilos en caso de error
                    element.style.display = originalDisplay;
                    if (header) header.style.display = originalHeaderDisplay;
                    
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Generación',
                            text: 'Ocurrió un error al generar el PDF.',
                            confirmButtonText: 'Cerrar'
                        });
                    }
                });
            }, 1500); // Aumentado de 500ms a 1500ms para asegurar renderizado completo
        }
    </script>
    
    <script>
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
    </script>
</div>


