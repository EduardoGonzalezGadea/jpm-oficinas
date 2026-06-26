<div class="modal fade" id="modalConfirmacionCfe" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-full-width" role="document">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-primary text-white p-2">
                <h5 class="modal-title m-0">
                    <i class="fas fa-file-invoice mr-2"></i>
                    Confirmar carga de CFE
                    @if(!empty($datosExtraidos['documento_tipo']))
                        &mdash; <strong>{{ $datosExtraidos['documento_tipo'] }}</strong>
                    @endif
                    @if(!empty($datosExtraidos['documento_serie']) || !empty($datosExtraidos['documento_numero']))
                        Serie {{ $datosExtraidos['documento_serie'] ?? '' }} Nº
                        {{ $datosExtraidos['documento_numero'] ?? '' }}
                    @endif
                </h5>
                <button type="button" class="close text-white" aria-label="Close"
                    wire:click="cancelarCarga">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">

                @if(!empty($datosExtraidos))

                    {{-- Fila de datos principales --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card card-body py-2 px-3 h-100">
                                <h6 class="text-uppercase small font-weight-bold mb-2">
                                    <i class="fas fa-file-alt mr-1 text-info"></i> Documento
                                </h6>
                                <p class="mb-1 small"><strong>Archivo:</strong> {{ $nombreArchivoOriginal }}</p>
                                <p class="mb-1 small"><strong>Fecha:</strong>
                                    {{ !empty($datosExtraidos['fecha']) ? \Carbon\Carbon::parse($datosExtraidos['fecha'])->format('d/m/Y') : 'N/A' }}
                                </p>
                                <p class="mb-1 small"><strong>Moneda:</strong>
                                    {{ ($datosExtraidos['moneda'] ?? 'UYU') === 'UYU' ? '$' : ($datosExtraidos['moneda'] ?? 'UYU') }}
                                </p>
                                <p class="mb-1 small"><strong>Forma de Pago:</strong>
                                    {{ $datosExtraidos['forma_pago'] ?? 'N/A' }}</p>
                                @if(!empty($datosExtraidos['periodo']))
                                    <p class="mb-0 small"><strong>Período:</strong> {{ $datosExtraidos['periodo'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card card-body py-2 px-3 h-100">
                                <h6 class="text-uppercase small font-weight-bold mb-2">
                                    <i class="fas fa-building mr-1 text-info"></i> Emisor
                                </h6>
                                <p class="mb-1 small"><strong>Nombre:</strong>
                                    {{ $datosExtraidos['emisor_nombre'] ?? '—' }}</p>
                                <p class="mb-1 small"><strong>RUC:</strong> {{ $datosExtraidos['emisor_ruc'] ?? '—' }}
                                </p>
                                @if(!empty($datosExtraidos['emisor_direccion']))
                                    <p class="mb-0 small">
                                        {{ $datosExtraidos['emisor_direccion'] }}{{ !empty($datosExtraidos['emisor_localidad']) ? ', ' . $datosExtraidos['emisor_localidad'] : '' }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card card-body py-2 px-3 h-100">
                                <h6 class="text-uppercase small font-weight-bold mb-2">
                                    <i class="fas fa-user mr-1 text-info"></i> Receptor
                                </h6>
                                <p class="mb-1 small"><strong>Nombre:</strong>
                                    {{ $datosExtraidos['receptor_nombre_denominacion'] ?? 'Consumidor Final' }}</p>
                                <p class="mb-1 small"><strong>RUC/CI:</strong>
                                    {{ $datosExtraidos['receptor_documento_ruc'] ?? '—' }}</p>
                                @if(!empty($datosExtraidos['receptor_domicilio_fiscal']))
                                    <p class="mb-0 small">{{ $datosExtraidos['receptor_domicilio_fiscal'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Selectores de Concepto y SIIF --}}
                    <div class="border-top pt-3 mt-2 mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-uppercase small font-weight-bold mb-2">
                                    <i class="fas fa-tag mr-1 text-primary"></i> Concepto de Caja
                                </h6>
                                <div class="form-group mb-0">
                                    <select wire:model="cajaConceptoSeleccionado" id="selectorCajaConcepto"
                                        class="form-control @error('cajaConceptoSeleccionado') is-invalid @enderror">
                                        <option value="">— Seleccione concepto —</option>
                                        @foreach($cajaConceptos as $concepto)
                                            <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
                                        @endforeach
                                    </select>
                                    @error('cajaConceptoSeleccionado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Concepto al que corresponde el CFE.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-uppercase small font-weight-bold mb-2">
                                    <i class="fas fa-sitemap mr-1 text-primary"></i> Dependencia
                                </h6>
                                <div class="form-group mb-0">
                                    <select wire:model="siifDependenciaSeleccionado" id="selectorSiifDependencia"
                                        class="form-control @error('siifDependenciaSeleccionado') is-invalid @enderror">
                                        <option value="">— Seleccione dep. SIIF —</option>
                                        @foreach($siifDependencias as $dep)
                                            <option value="{{ $dep->id }}">{{ $dep->abreviatura }} - {{ $dep->dependencia }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('siifDependenciaSeleccionado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Dependencia asignada.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Ítems --}}
                    <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                        <i class="fas fa-list mr-1"></i> Ítems
                    </h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Detalle</th>
                                    <th class="text-center" style="width:10%">Cant.</th>
                                    <th class="text-right" style="width:18%">Precio</th>
                                    <th class="text-right" style="width:18%">Importe</th>
                                </tr>
                            </thead>
                                @foreach($datosExtraidos['items'] ?? [] as $index => $item)
                                <tbody class="item-distribucion-group">
                                    <tr>
                                        <td>
                                            {{ $item['detalle'] ?? '' }}
                                            @if(!empty($item['descripcion']))
                                                <br><small>{{ $item['descripcion'] }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            {{ number_format($item['cantidad'] ?? 1, 2, ',', '.') }}</td>
                                        <td class="text-right align-middle">
                                            {{ number_format($item['precio'] ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-right align-middle font-weight-bold">
                                            {{ number_format($item['importe'] ?? 0, 2, ',', '.') }}</td>
                                    </tr>
                                    <tr class="table-secondary">
                                        <td colspan="4" class="py-1 px-3">
                                            <div class="d-flex align-items-center">
                                                <span class="mr-2 font-weight-bold small">
                                                    <i class="fas fa-sitemap mr-1 text-primary"></i> Distribución SIIF:
                                                </span>
                                                <div class="flex-grow-1" style="max-width: 400px;">
                                                    @if($cajaConceptoSeleccionado && $siifDependenciaSeleccionado)
                                                        <select wire:model="itemDistribuciones.{{ $index }}"
                                                            class="form-control form-control-sm @error('itemDistribuciones.'.$index) is-invalid @enderror">
                                                            <option value="">— Sin asignar —</option>
                                                            @foreach($distribuciones as $dist)
                                                                <option value="{{ $dist->id }}">
                                                                    {{ $dist->concepto }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('itemDistribuciones.'.$index)
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    @else
                                                        <span class="small text-info">
                                                            <i class="fas fa-info-circle mr-1"></i> Seleccione concepto y dependencia en la cabecera
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                @endforeach
                        </table>
                    </div>

                    {{-- Medios de Pago + Total --}}
                    <div class="row align-items-center mb-3">
                        <div class="col-md-7">
                            <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                <i class="fas fa-money-bill-wave mr-1"></i> Medios de Pago
                            </h6>
                            <ul class="list-unstyled mb-0 small">
                                @forelse($datosExtraidos['medios_pago'] ?? [] as $mp)
                                    <li class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        <span class="font-weight-bold mr-1">{{ $mp['tipo'] ?: 'Medio de pago' }}:</span>
                                        <strong>
                                            {{ ($datosExtraidos['moneda'] ?? 'UYU') === 'UYU' ? '$' : ($datosExtraidos['moneda'] ?? '') }}
                                            {{ number_format($mp['valor'] ?? 0, 2, ',', '.') }}
                                        </strong>
                                    </li>
                                @empty
                                    <li>No se extrajeron medios de pago explícitos.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-md-5 text-right">
                            <p class="small text-uppercase font-weight-bold mb-0">Total a Pagar</p>
                            <h3 class="font-weight-bold mb-0 text-nowrap {{ ($datosExtraidos['total_a_pagar'] ?? 0) < 0 ? 'text-danger' : 'text-info' }}"
                                style="letter-spacing:-1px">
                                {{ ($datosExtraidos['moneda'] ?? 'UYU') === 'UYU' ? '$' : ($datosExtraidos['moneda'] ?? '') }}
                                {{ number_format($datosExtraidos['total_a_pagar'] ?? 0, 2, ',', '.') }}
                            </h3>
                        </div>
                    </div>

                    {{-- Referencias y Adenda --}}
                    @if(!empty($datosExtraidos['referencias']) || !empty($datosExtraidos['adenda']))
                        <div class="row mb-3">
                            @if(!empty($datosExtraidos['referencias']))
                                <div class="col-{{ !empty($datosExtraidos['adenda']) ? 'md-6' : '12' }}">
                                    <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                        <i class="fas fa-link mr-1"></i> Referencias
                                    </h6>
                                    <p class="small p-2 rounded text-wrap text-break mb-0 border">
                                        {{ $datosExtraidos['referencias'] }}</p>
                                </div>
                            @endif
                            @if(!empty($datosExtraidos['adenda']))
                                <div class="col-{{ !empty($datosExtraidos['referencias']) ? 'md-6' : '12' }}">
                                    <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                        <i class="fas fa-sticky-note mr-1"></i> Adenda
                                    </h6>
                                    <p class="small p-2 rounded text-wrap text-break mb-0 border">
                                        {{ $datosExtraidos['adenda'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif

            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" wire:click="cancelarCarga">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" wire:click="confirmarCarga"
                    wire:loading.attr="disabled" wire:target="confirmarCarga">
                    <span wire:loading.remove wire:target="confirmarCarga">
                        <i class="fas fa-save mr-1"></i> Confirmar y Guardar
                    </span>
                    <span wire:loading wire:target="confirmarCarga">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Guardando...
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>
