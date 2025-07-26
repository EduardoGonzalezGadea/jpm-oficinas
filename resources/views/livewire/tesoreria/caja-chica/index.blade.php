<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Selector de Fecha/Mes/Año -->
    <div class="form-row mb-3">
        <div class="col-md-3">
            <label for="mesSelector">Mes:</label>
            <select id="mesSelector" class="form-control" wire:model.live="mesActual">
                <option value="enero">Enero</option>
                <option value="febrero">Febrero</option>
                <option value="marzo">Marzo</option>
                <option value="abril">Abril</option>
                <option value="mayo">Mayo</option>
                <option value="junio">Junio</option>
                <option value="julio">Julio</option>
                <option value="agosto">Agosto</option>
                <option value="setiembre">Setiembre</option>
                <option value="octubre">Octubre</option>
                <option value="noviembre">Noviembre</option>
                <option value="diciembre">Diciembre</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="anioSelector">Año:</label>
            <input type="number" id="anioSelector" class="form-control" wire:model.live="anioActual">
        </div>
        <div class="col-md-2 align-self-end">
            <button class="btn btn-primary" wire:click="cargarDatos">Actualizar</button>
        </div>
        <div class="col-md-3 align-self-end">
            <!-- Corregido: wire:click llama al método PHP -->
            <button class="btn btn-success" wire:click="mostrarModalNuevoFondo">Nuevo Fondo</button>
            <button class="btn btn-info ml-2" wire:click="prepararModalNuevoPendiente">Nuevo Pendiente</button>
            <button class="btn btn-warning ml-2" wire:click="prepararModalNuevoPago">Nuevo Pago</button>
        </div>
    </div>

    <!-- Tabla Caja Chica (Fondo Permanente) -->
    <h4 class="mt-4">Fondo Permanente</h4>
    <table class="table table-striped table-bordered" id="tablaCajaChica">
        <thead class="thead-dark">
            <tr>
                <th class="text-center">Mes</th>
                <th class="text-center">Año</th>
                <th class="text-center">Monto</th>
                <th class="text-center noImprimir">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaCajaChica as $item)
                <tr wire:key="cajachica-{{ $item->idCajaChica }}">
                    <td class="text-center">{{ $item->mes }}</td>
                    <td class="text-center">{{ $item->anio }}</td>
                    <td class="text-center classCajaChicaActual">{{ number_format($item->montoCajaChica, 2, ',', '.') }}
                    </td>
                    <td class="text-center noImprimir">
                        <button class="btn btn-sm btn-warning"
                            wire:click="editarFondo({{ $item->idCajaChica }}, {{ $item->montoCajaChica }})">
                            <i class="fas fa-pencil-alt"></i> Editar
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No hay datos de Fondo Permanente para el mes y año
                        seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tabla Pendientes Detalle -->
    <h4 class="mt-4">Pendientes</h4>
    <table class="table table-striped table-bordered" id="tablaPendientesDetalle">
        <thead class="thead-light">
            <tr>
                <th class="text-right">N&deg;</th>
                <th class="text-center">FECHA</th>
                <th class="text-center">DEPENDENCIA</th>
                <th class="text-right">MONTO</th>
                <th class="text-right">EXTRA</th>
                <th class="text-right">REINTEGRADO</th>
                <th class="text-right">RECUPERADO</th>
                <th class="text-right">SALDO</th>
                <th class="text-center noImprimir">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaPendientesDetalle as $item)
                <tr wire:key="pendiente-{{ $item->idPendientes }}">
                    <td class="text-right">{{ $item->pendiente }}</td>
                    <td class="text-center">{{ $item->fechaPendientes ? $item->fechaPendientes->format('d/m/Y') : '' }}
                    </td>
                    <td class="text-center">{{ $item->dependencia->dependencia ?? '' }}</td>
                    <td class="text-right">{{ number_format($item->montoPendientes, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->extra, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->tot_reintegrado, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->tot_recuperado, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->saldo, 2, ',', '.') }}</td>
                    {{-- Acciones --}}
                    <td class="text-center noImprimir align-middle">
                        <input type='hidden' name='selIdPendientes' value='{{ $item->idPendientes }}'>
                        <div class='btn-group' role='group'>
                            <!-- Botones existentes -->
                            <button name='btnEditar' type='button' class='btn btn-sm btn-dark mr-1' title='Editar'><i
                                    class='fas fa-pencil-alt'></i></button>
                            <!-- Nuevo botón de impresión -->
                            <a href="{{ route('tesoreria.caja-chica.imprimir.pendiente', $item->idPendientes) }}"
                                target="_blank" class="btn btn-sm btn-dark mr-1" title="Imprimir Pendiente">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No hay datos de Pendientes para el mes y año seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tabla Pagos -->
    <h4 class="mt-4">Pagos Directos</h4>
    <table class="table table-striped table-bordered" id="tablaPagos">
        <thead class="thead-light">
            <tr>
                <th class="text-center">FECHA EGRESO</th>
                <th class="text-center">EGRESO</th>
                <th class="text-center">ACREEDOR</th>
                <th>CONCEPTO</th>
                <th class="text-right">MONTO</th>
                <th class="text-center">FECHA INGRESO</th>
                <th class="text-center">INGRESO</th>
                <th class="text-right">RECUPERADO</th>
                <th class="text-right">SALDO</th>
                <th class="text-center noImprimir">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaPagos as $item)
                <tr wire:key="pago-{{ $item->idPagos }}">
                    <td class="text-center">
                        {{ $item->fechaEgresoPagos ? $item->fechaEgresoPagos->format('d/m/Y') : '' }}</td>
                    <td class="text-center">{{ $item->egresoPagos }}</td>
                    <td class="text-center">{{ $item->acreedor->acreedor ?? '' }}</td>
                    <td>{{ $item->conceptoPagos }}</td>
                    <td class="text-right">{{ number_format($item->montoPagos, 2, ',', '.') }}</td>
                    <td class="text-center">
                        {{ $item->fechaIngresoPagos ? $item->fechaIngresoPagos->format('d/m/Y') : '' }}</td>
                    <td class="text-center">{{ $item->ingresoPagos }}</td>
                    <td class="text-right">{{ number_format($item->recuperadoPagos, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->saldo_pagos, 2, ',', '.') }}</td>
                    <td class="text-center noImprimir align-middle">
                        <input type='hidden' name='selIdPagos' value='{{ $item->idPagos }}'>
                        <div class='btn-group' role='group'>
                            <!-- Botones existentes -->
                            <button name='btnEditar' type='button' class='btn btn-sm btn-dark mr-1'
                                title='Editar'><i class='fas fa-pencil-alt'></i></button>
                            <!-- Nuevo botón de impresión -->
                            <a href="{{ route('tesoreria.caja-chica.imprimir.pago', $item->idPagos) }}"
                                target="_blank" class="btn btn-sm btn-dark mr-1" title="Imprimir Pago Directo">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No hay datos de Pagos Directos para el mes y año
                        seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Tabla Totales -->
    <h4 class="mt-4">Totales</h4>

    <div class="form-group mb-3">
        <label for="fechaHastaInput">Fecha Hasta:</label>
        <input type="text" id="fechaHastaInput" class="form-control datepicker" wire:model.live="fechaHasta"
            readonly>
        <button class="btn btn-secondary btn-sm mt-1"
            wire:click="$set('fechaHasta', now()->format('d/m/Y'))">Limpiar</button>
        <button class="btn btn-success ml-2" wire:click="exportarExcel">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </button>
    </div>
    <table class="table table-bordered" id="tablaTotales">
        <thead class="thead-dark">
            <tr>
                <th>Concepto</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tablaTotales as $concepto => $valor)
                <tr>
                    <td>{{ $concepto }}</td>
                    <td class="text-right">{{ is_numeric($valor) ? number_format($valor, 2, ',', '.') : $valor }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center">No hay datos de totales.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Incluir los componentes de modales -->
    <livewire:tesoreria.caja-chica.modal-nuevo-fondo />
    <livewire:tesoreria.caja-chica.modal-nuevo-pendiente />
    <livewire:tesoreria.caja-chica.modal-nuevo-pago />

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', function() {
                // Inicializar datepicker si es necesario
                Livewire.on('contentChanged', function() {
                    if ($.fn.datepicker) {
                        $('.datepicker').not('.hasDatepicker').datepicker({
                            dateFormat: 'dd/mm/yy',
                            changeMonth: true,
                            changeYear: true,
                        });
                    }
                });

                // --- Listener para mostrar el modal de nuevo fondo ---
                Livewire.on('mostrar-modal-nuevo-fondo', function() {
                    // console.log('Evento JS: mostrar-modal-nuevo-fondo recibido');
                    if ($('#modalNuevoFondo').length) {
                        $('#modalNuevoFondo').modal('show');
                    } else {
                        console.error('Error: Elemento #modalNuevoFondo no encontrado en el DOM.');
                    }
                });

                Livewire.on('cerrar-modal-nuevo-fondo', function() {
                    // console.log('Evento JS: cerrar-modal-nuevo-fondo recibido');
                    if ($('#modalNuevoFondo').length) {
                        $('#modalNuevoFondo').modal('hide');
                    }
                });

            });
        </script>
    @endpush
</div>
