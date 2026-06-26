@php $simbolo = $cfe->moneda === 'UYU' ? '$' : $cfe->moneda; @endphp
<div class="modal fade" id="modalCfe{{ $cfe->id }}" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-full-width" role="document">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-info text-white p-2">
                <h5 class="modal-title m-0">
                    <i class="fas fa-file-invoice mr-2"></i>
                    <strong>{{ $cfe->documento_tipo }}</strong>
                    &mdash; Serie {{ $cfe->documento_serie }} Nº {{ $cfe->documento_numero }}
                    @if($cfe->comprobante_tipo)
                        <span class="badge badge-light ml-2">{{ $cfe->comprobante_tipo }}</span>
                    @endif
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-3">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card card-body py-2 px-3 h-100">
                            <h6 class="text-uppercase small font-weight-bold mb-2">
                                <i class="fas fa-file-alt mr-1 text-info"></i> Documento
                            </h6>
                            <p class="mb-1 small"><strong>Fecha:</strong>
                                {{ $cfe->fecha ? $cfe->fecha->format('d/m/Y') : 'N/A' }}</p>
                            <p class="mb-1 small"><strong>Moneda:</strong> {{ $simbolo }}</p>
                            <p class="mb-1 small"><strong>Forma de Pago:</strong> {{ $cfe->forma_pago ?: 'N/A' }}
                            </p>
                            @if($cfe->periodo)
                                <p class="mb-0 small"><strong>Período:</strong> {{ $cfe->periodo }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-body py-2 px-3 h-100">
                            <h6 class="text-uppercase small font-weight-bold mb-2">
                                <i class="fas fa-building mr-1 text-info"></i> Emisor
                            </h6>
                            <p class="mb-1 small"><strong>Nombre:</strong> {{ $cfe->emisor_nombre }}</p>
                            <p class="mb-1 small"><strong>RUC:</strong> {{ $cfe->emisor_ruc }}</p>
                            @if($cfe->emisor_direccion)
                                <p class="mb-0 small">
                                    {{ $cfe->emisor_direccion }}{{ $cfe->emisor_localidad ? ', ' . $cfe->emisor_localidad : '' }}
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
                                {{ $cfe->receptor_nombre_denominacion ?: 'Consumidor Final' }}</p>
                            <p class="mb-1 small"><strong>RUC/CI:</strong> {{ $cfe->receptor_documento_ruc ?: '—' }}
                            </p>
                            @if($cfe->receptor_domicilio_fiscal)
                                <p class="mb-0 small">{{ $cfe->receptor_domicilio_fiscal }}</p>
                            @endif
                        </div>
                    </div>
                </div>

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
                        <tbody>
                            @foreach($cfe->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->detalle }}
                                        @if($item->descripcion)
                                            <br><small>{{ $item->descripcion }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        {{ number_format($item->cantidad, 2, ',', '.') }}</td>
                                    <td class="text-right align-middle">{{ number_format($item->precio, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right align-middle font-weight-bold">
                                        {{ number_format($item->importe, 2, ',', '.') }}</td>
                                </tr>
                                <tr class="table-secondary">
                                    <td colspan="4" class="py-1 px-3">
                                        <span class="font-weight-bold small mr-2">
                                            <i class="fas fa-sitemap mr-1 text-primary"></i> Distribución SIIF:
                                        </span>
                                        @if($item->siifDistribucion)
                                            <span class="badge badge-info text-wrap">
                                                {{ $item->siifDistribucion->concepto }}
                                            </span>
                                        @else
                                            <span class="small text-info">Sin asignar</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row align-items-center mb-3">
                    <div class="col-md-7">
                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                            <i class="fas fa-money-bill-wave mr-1"></i> Medios de Pago
                        </h6>
                        <ul class="list-unstyled mb-0 small">
                            @forelse($cfe->mediosPago as $mp)
                                <li class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success mr-2"></i>
                                    <span class="font-weight-bold mr-1">{{ $mp->medio_pago_tipo ?: 'Medio de pago' }}:</span>
                                    <strong>{{ $simbolo }}
                                        {{ number_format($mp->medio_pago_valor, 2, ',', '.') }}</strong>
                                </li>
                            @empty
                                <li>No se extrajeron medios de pago explícitos.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="col-md-5 text-right">
                        <p class="small text-uppercase font-weight-bold mb-0">Total a Pagar</p>
                        <h3 class="font-weight-bold mb-0 text-nowrap {{ $cfe->total_a_pagar < 0 ? 'text-danger' : 'text-info' }}"
                            style="letter-spacing:-1px">
                            {{ $simbolo }} {{ number_format($cfe->total_a_pagar, 2, ',', '.') }}
                        </h3>
                    </div>
                </div>

                @if($cfe->referencias || $cfe->adenda)
                    <div class="row mb-3">
                        @if($cfe->referencias)
                            <div class="col-{{ $cfe->adenda ? 'md-6' : '12' }}">
                                <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                    <i class="fas fa-link mr-1"></i> Referencias
                                </h6>
                                <p class="small p-2 rounded text-wrap text-break mb-0 border">{{ $cfe->referencias }}</p>
                            </div>
                        @endif
                        @if($cfe->adenda)
                            <div class="col-{{ $cfe->referencias ? 'md-6' : '12' }}">
                                <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                                    <i class="fas fa-sticky-note mr-1"></i> Adenda
                                </h6>
                                <p class="small p-2 rounded text-wrap text-break mb-0 border">{{ $cfe->adenda }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                            <i class="fas fa-tag mr-1"></i> Concepto de Caja
                        </h6>
                        @if($cfe->cajaConcepto)
                            <span class="badge badge-success px-3 py-2" style="font-size:0.9rem">
                                {{ $cfe->cajaConcepto->caja_concepto }}
                            </span>
                        @else
                            <span class="badge badge-warning px-3 py-2" style="font-size:0.9rem">Sin concepto
                                asignado</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase small font-weight-bold mb-1 border-bottom pb-1">
                            <i class="fas fa-sitemap mr-1"></i> Dependencia
                        </h6>
                        @if($cfe->siifDistribucionDependencia)
                            <span class="badge badge-info px-3 py-2" style="font-size:0.9rem">
                                {{ $cfe->siifDistribucionDependencia->abreviatura }} -
                                {{ $cfe->siifDistribucionDependencia->dependencia }}
                            </span>
                        @else
                            <span class="badge badge-secondary px-3 py-2" style="font-size:0.9rem">Sin dep.
                                asignada</span>
                        @endif
                    </div>
                </div>

            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
