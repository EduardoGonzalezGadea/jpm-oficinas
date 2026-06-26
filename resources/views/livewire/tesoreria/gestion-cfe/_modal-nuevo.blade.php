<div class="modal fade" id="modalNuevoCfe" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
<div class="modal-dialog modal-full-width" role="document">
    <div class="modal-content border-0 shadow">

        <div class="modal-header bg-success text-white p-2">
            <h5 class="modal-title m-0">
                <i class="fas fa-plus-circle mr-2"></i>
                <strong>Nuevo CFE</strong>
                &mdash; <span class="small">Creación manual</span>
            </h5>
            <button type="button" class="close text-white" aria-label="Close"
                wire:click="cancelarNuevo">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="modal-body p-3">

            <div class="row mb-3">
                <div class="col-md-3">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-file-alt mr-1 text-success"></i> Tipo de Documento
                    </h6>
                    <div class="form-group mb-0">
                        <select wire:model="nuevoDocumentoTipo"
                            class="form-control @error('nuevoDocumentoTipo') is-invalid @enderror">
                            <option value="">— Seleccione tipo —</option>
                            <option value="E-Factura Cobranza">E-Factura Cobranza</option>
                            <option value="E-Ticket Cobranza">E-Ticket Cobranza</option>
                        </select>
                        @error('nuevoDocumentoTipo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-1">
                    <h6 class="small font-weight-bold mb-2">
                        Serie
                    </h6>
                    <div class="form-group mb-0">
                        <input type="text" wire:model="nuevoDocumentoSerie"
                            class="form-control @error('nuevoDocumentoSerie') is-invalid @enderror"
                            placeholder="A">
                        @error('nuevoDocumentoSerie')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-hashtag mr-1 text-success"></i> Número
                    </h6>
                    <div class="form-group mb-0">
                        <input type="text" wire:model="nuevoDocumentoNumero"
                            class="form-control @error('nuevoDocumentoNumero') is-invalid @enderror"
                            placeholder="123456">
                        @error('nuevoDocumentoNumero')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-calendar mr-1 text-success"></i> Fecha
                    </h6>
                    <div class="form-group mb-0">
                        <input type="date" wire:model="nuevoFecha"
                            class="form-control @error('nuevoFecha') is-invalid @enderror">
                        @error('nuevoFecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-user mr-1 text-success"></i> Receptor
                    </h6>
                    <div class="form-group mb-1">
                        <input type="text" wire:model="nuevoReceptorNombre"
                            class="form-control @error('nuevoReceptorNombre') is-invalid @enderror"
                            placeholder="Nombre del receptor">
                        @error('nuevoReceptorNombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <input type="text" wire:model="nuevoReceptorRuc"
                            class="form-control" placeholder="RUC/CI (opcional)">
                    </div>
                </div>
            </div>

            <div class="border-top pt-3 mt-2 mb-3">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-uppercase small font-weight-bold mb-2">
                            <i class="fas fa-tag mr-1 text-primary"></i> Concepto de Caja
                        </h6>
                        <div class="form-group mb-0">
                            <select wire:model="nuevoCajaConceptoSeleccionado"
                                class="form-control @error('nuevoCajaConceptoSeleccionado') is-invalid @enderror">
                                <option value="">— Seleccione concepto —</option>
                                @foreach($cajaConceptos as $concepto)
                                    <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
                                @endforeach
                            </select>
                            @error('nuevoCajaConceptoSeleccionado')
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
                            <select wire:model="nuevoSiifDependenciaSeleccionado"
                                class="form-control @error('nuevoSiifDependenciaSeleccionado') is-invalid @enderror">
                                <option value="">— Seleccione dep. SIIF —</option>
                                @foreach($siifDependencias as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->abreviatura }} - {{ $dep->dependencia }}
                                    </option>
                                @endforeach
                            </select>
                            @error('nuevoSiifDependenciaSeleccionado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Dependencia asignada.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1">
                <h6 class="text-uppercase small font-weight-bold mb-0">
                    <i class="fas fa-list mr-1"></i> Ítems
                </h6>
                <button type="button" class="btn btn-sm btn-outline-success" wire:click="agregarItemNuevo">
                    <i class="fas fa-plus mr-1"></i> Agregar ítem
                </button>
            </div>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:35%">Detalle</th>
                            <th class="text-center" style="width:10%">Cant.</th>
                            <th class="text-right" style="width:15%">Precio</th>
                            <th class="text-right" style="width:15%">Importe</th>
                            <th style="width:20%">Distribución SIIF</th>
                            <th class="text-center" style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nuevoItems as $index => $item)
                            <tr wire:key="nuevo-item-{{ $index }}">
                                <td>
                                    <input type="text" wire:model="nuevoItems.{{ $index }}.detalle"
                                        class="form-control form-control-sm @error('nuevoItems.'.$index.'.detalle') is-invalid @enderror"
                                        placeholder="Detalle del ítem">
                                    @error('nuevoItems.'.$index.'.detalle')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <input type="number" step="1" min="1"
                                        wire:model="nuevoItems.{{ $index }}.cantidad"
                                        class="form-control form-control-sm text-center"
                                        wire:change="recalcularImporteItemNuevo({{ $index }})">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                        wire:model="nuevoItems.{{ $index }}.precio"
                                        class="form-control form-control-sm text-right"
                                        wire:change="recalcularImporteItemNuevo({{ $index }})">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                        wire:model="nuevoItems.{{ $index }}.importe"
                                        class="form-control form-control-sm text-right font-weight-bold @error('nuevoItems.'.$index.'.importe') is-invalid @enderror">
                                    @error('nuevoItems.'.$index.'.importe')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    @if($nuevoCajaConceptoSeleccionado && $nuevoSiifDependenciaSeleccionado)
                                        <select wire:model="nuevoItemDistribuciones.{{ $index }}"
                                            class="form-control form-control-sm @error('nuevoItemDistribuciones.'.$index) is-invalid @enderror">
                                            <option value="">— Sin asignar —</option>
                                            @foreach($nuevoDistribuciones as $dist)
                                                <option value="{{ $dist->id }}">{{ $dist->concepto }}</option>
                                            @endforeach
                                        </select>
                                        @error('nuevoItemDistribuciones.'.$index)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <span class="small text-info">
                                            <i class="fas fa-info-circle mr-1"></i> Seleccione concepto y dependencia
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if(count($nuevoItems) > 1)
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="quitarItemNuevo({{ $index }})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-2 text-muted small">
                                    No hay ítems. Presione <strong>Agregar ítem</strong> para comenzar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-active font-weight-bold">
                            <td colspan="3" class="text-right">TOTAL</td>
                            <td class="text-right">
                                $ {{ number_format(collect($nuevoItems)->sum(fn($i) => (float)($i['importe'] ?? 0)), 2, ',', '.') }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1">
                <h6 class="text-uppercase small font-weight-bold mb-0">
                    <i class="fas fa-money-bill-wave mr-1"></i> Medios de Pago
                </h6>
                <button type="button" class="btn btn-sm btn-outline-success" wire:click="agregarMedioPagoNuevo">
                    <i class="fas fa-plus mr-1"></i> Agregar medio de pago
                </button>
            </div>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tipo</th>
                            <th class="text-right" style="width:20%">Valor</th>
                            <th class="text-center" style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nuevoMediosPago as $index => $mp)
                            <tr wire:key="nuevo-mp-{{ $index }}">
                                <td>
                                    <select wire:model="nuevoMediosPago.{{ $index }}.tipo"
                                        class="form-control form-control-sm">
                                        <option value="">— Seleccione —</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                        wire:model="nuevoMediosPago.{{ $index }}.valor"
                                        class="form-control form-control-sm text-right">
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="quitarMedioPagoNuevo({{ $index }})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-2 text-muted small">
                                    No hay medios de pago registrados. (Opcional)
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-link mr-1 text-secondary"></i> Referencias
                    </h6>
                    <textarea wire:model="nuevoReferencias" class="form-control" rows="2"
                        placeholder="Referencias del documento (opcional)"></textarea>
                </div>
                <div class="col-md-6">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-sticky-note mr-1 text-secondary"></i> Adenda
                    </h6>
                    <textarea wire:model="nuevoAdenda" class="form-control" rows="2"
                        placeholder="Adenda o comentarios adicionales (opcional)"></textarea>
                </div>
            </div>

        </div>

        <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary" wire:click="cancelarNuevo">
                <i class="fas fa-times mr-1"></i> Cancelar
            </button>
            <button type="button" class="btn btn-success" wire:click="guardarNuevo"
                wire:loading.attr="disabled" wire:target="guardarNuevo">
                <span wire:loading.remove wire:target="guardarNuevo">
                    <i class="fas fa-save mr-1"></i> Guardar CFE
                </span>
                <span wire:loading wire:target="guardarNuevo">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Guardando...
                </span>
            </button>
        </div>

    </div>
</div>
</div>
