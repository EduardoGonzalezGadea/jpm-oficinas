<div>
    <div class="modal-backdrop fade @if($show) show d-block @else d-none @endif" style="z-index: 1040;"></div>
    <div id="modalEditarPago" class="modal fade @if($show) show d-block @endif" tabindex="-1" role="dialog" aria-hidden="{{ $show ? 'false' : 'true' }}" style="z-index: 1050; {{ $show ? 'background-color: rgba(0,0,0,0.5);' : 'display: none;' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content modal-animate-in border-0 shadow">
                <div class="modal-header bg-light border-bottom-0 pb-2">
                    <h5 class="modal-title text-dark font-weight-bold" style="font-size: 1.1rem;">
                        <i class="fas fa-file-invoice text-primary mr-2"></i>Editar Pago
                    </h5>
                    <button type="button" class="close" wire:click="cerrarModal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body pt-2 pb-3 bg-white">
                        
                        <!-- SECCIÓN 1: DATOS DEL PAGO -->
                        <fieldset class="border p-2 mb-3 rounded position-relative shadow-sm" style="background-color: #fcfcfc; border-color: #e9ecef !important;">
                            <legend class="w-100 d-flex justify-content-between align-items-center px-2 m-0" style="font-size: 0.85rem; font-weight: 700; transform: translateY(-10px); background: #fcfcfc;">
                                <span class="text-primary">Datos del Egreso</span>
                            </legend>
                            
                            <div class="form-row px-2">
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Fecha Egreso</label>
                                    <input type="date" class="form-control form-control-sm @error('pago.fechaEgresoPagos') is-invalid @enderror" wire:model.defer="pago.fechaEgresoPagos">
                                    @error('pago.fechaEgresoPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Fecha Efec.</label>
                                    <input type="date" class="form-control form-control-sm @error('pago.fechaEgresoEfectivoPagos') is-invalid @enderror" wire:model.defer="pago.fechaEgresoEfectivoPagos" title="Fecha Egreso Efectivo">
                                    @error('pago.fechaEgresoEfectivoPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Nro. Egreso</label>
                                    <input type="text" class="form-control form-control-sm @error('pago.egresoPagos') is-invalid @enderror" wire:model.defer="pago.egresoPagos" placeholder="Opcional">
                                    @error('pago.egresoPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Monto Otorgado</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white">$</span></div>
                                        <input type="number" class="form-control form-control-sm font-weight-bold @error('pago.montoPagos') is-invalid @enderror" step="0.01" wire:model.lazy="pago.montoPagos">
                                    </div>
                                    @error('pago.montoPagos') <span class="text-danger d-block mt-1" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="form-row px-2">
                                <div class="form-group col-md-4 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Acreedor</label>
                                    <select class="form-control form-control-sm @error('pago.relAcreedores') is-invalid @enderror" wire:model.defer="pago.relAcreedores">
                                        <option value="">Seleccione</option>
                                        @foreach ($acreedores as $acreedor)
                                        <option value="{{ $acreedor->idAcreedores }}">{{ $acreedor->acreedor }}</option>
                                        @endforeach
                                    </select>
                                    @error('pago.relAcreedores') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-8 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Concepto</label>
                                    <input type="text" class="form-control form-control-sm @error('pago.conceptoPagos') is-invalid @enderror" wire:model.defer="pago.conceptoPagos" placeholder="Descripción del pago">
                                    @error('pago.conceptoPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <!-- SECCIÓN 2: RENDICIÓN -->
                        <fieldset class="border p-2 mb-3 rounded position-relative shadow-sm" style="background-color: #fcfcfc; border-color: #e9ecef !important;">
                            <legend class="w-100 d-flex justify-content-between align-items-center px-2 m-0" style="font-size: 0.85rem; font-weight: 700; transform: translateY(-10px); background: #fcfcfc;">
                                <span class="text-success">Rendición</span>
                                @if($pago['rendidoPagos'] !== '' || $pago['fechaRendicionPagos'] !== '')
                                    @if(!empty($pago['recuperadoPagos']) || !empty($pago['fechaIngresoPagos']) || !empty($pago['ingresoPagos']))
                                    <span class="text-muted" style="font-size: 0.65rem;" title="Debe eliminar primero los datos de recuperación">
                                        <i class="fas fa-lock mr-1"></i>Rendición bloqueada (hay recuperación)
                                    </span>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" style="padding: 0.1rem 0.5rem; font-size: 0.7rem; line-height: 1.2;" wire:click="eliminarRendicion" title="Eliminar datos de rendición">
                                        <i class="fas fa-trash-alt mr-1"></i>Eliminar rendición
                                    </button>
                                    @endif
                                @endif
                            </legend>
                            
                            <div class="form-row px-2">
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Monto Rendido</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white">$</span></div>
                                        <input type="number" class="form-control form-control-sm text-success font-weight-bold @error('pago.rendidoPagos') is-invalid @enderror" step="0.01" wire:model="pago.rendidoPagos" placeholder="Sin rendición">
                                    </div>
                                    @error('pago.rendidoPagos') <span class="text-danger d-block mt-1" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Fecha Rendición</label>
                                    <input type="date" class="form-control form-control-sm @error('pago.fechaRendicionPagos') is-invalid @enderror" wire:model.defer="pago.fechaRendicionPagos">
                                    @error('pago.fechaRendicionPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Reintegrado</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text bg-light">$</span></div>
                                        <input type="number" class="form-control form-control-sm bg-light @error('pago.reintegradoPagos') is-invalid @enderror" step="0.01" wire:model.defer="pago.reintegradoPagos" readonly tabindex="-1">
                                    </div>
                                    @error('pago.reintegradoPagos') <span class="text-danger d-block mt-1" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label class="small mb-1 font-weight-bold text-muted">Ingreso Reintegro</label>
                                    <input type="text" class="form-control form-control-sm @error('pago.ingresoReintegroPagos') is-invalid @enderror" wire:model.defer="pago.ingresoReintegroPagos" placeholder="Nro. Ingreso">
                                    @error('pago.ingresoReintegroPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            @if (($pago['extraPagos'] ?? 0) > 0)
                            <div class="alert alert-warning py-1 px-2 mb-1 mx-2" style="font-size: 0.75rem; border-left: 3px solid #ffc107;">
                                <i class="fas fa-exclamation-triangle mr-1"></i> <strong>Extra:</strong> ${{ number_format($pago['extraPagos'], 2, ',', '.') }} <span class="text-muted">(El monto rendido supera al otorgado)</span>
                            </div>
                            @endif
                        </fieldset>

                        <!-- SECCIÓN 3: INGRESO / RECUPERACIÓN -->
                        <fieldset class="border p-2 mb-1 rounded position-relative shadow-sm" style="background-color: #fcfcfc; border-color: #e9ecef !important;">
                            <legend class="w-100 d-flex justify-content-between align-items-center px-2 m-0" style="font-size: 0.85rem; font-weight: 700; transform: translateY(-10px); background: #fcfcfc;">
                                <span class="text-info">Ingreso / Recuperación</span>
                                @if(!empty($pago['recuperadoPagos']) || !empty($pago['fechaIngresoPagos']) || !empty($pago['ingresoPagos']))
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" style="padding: 0.1rem 0.5rem; font-size: 0.7rem; line-height: 1.2;" wire:click="eliminarRecuperacion" title="Eliminar datos de recuperación">
                                    <i class="fas fa-trash-alt mr-1"></i>Borrar recuperación
                                </button>
                                @endif
                            </legend>
                            
                            <div class="form-row px-2">
                                <div class="form-group col-md-4 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Monto Recuperado</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white">$</span></div>
                                        <input type="number" class="form-control form-control-sm text-info font-weight-bold @error('pago.recuperadoPagos') is-invalid @enderror" step="0.01" wire:model="pago.recuperadoPagos" placeholder="0.00">
                                    </div>
                                    @error('pago.recuperadoPagos') <span class="text-danger d-block mt-1" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-4 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Fecha Ingreso</label>
                                    <input type="date" class="form-control form-control-sm @error('pago.fechaIngresoPagos') is-invalid @enderror" wire:model.defer="pago.fechaIngresoPagos">
                                    @error('pago.fechaIngresoPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-4 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Nro. Ingreso</label>
                                    <input type="text" class="form-control form-control-sm @error('pago.ingresoPagos') is-invalid @enderror" wire:model.defer="pago.ingresoPagos">
                                    @error('pago.ingresoPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        @php
                            $esBSE = false;
                            if (!empty($pago['relAcreedores'])) {
                                $acreedorActual = collect($acreedores)->firstWhere('idAcreedores', $pago['relAcreedores']);
                                $esBSE = $acreedorActual && $acreedorActual->acreedor === 'Banco de Seguros del Estado';
                            }
                        @endphp

                        @if($esBSE)
                        <!-- SECCIÓN 4: DATOS ESPECÍFICOS BSE -->
                        <fieldset class="border p-2 mt-3 mb-1 rounded position-relative shadow-sm" style="background-color: #f8f9fa; border-color: #dee2e6 !important;">
                            <legend class="w-100 d-flex justify-content-between align-items-center px-2 m-0" style="font-size: 0.85rem; font-weight: 700; transform: translateY(-10px); background: #f8f9fa;">
                                <span class="text-secondary"><i class="fas fa-shield-alt mr-1"></i> Información BSE</span>
                                @if(!empty($pago['ingresoPagosBSE']) || !empty($pago['fechaIngresoBSEPagos']))
                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" style="padding: 0.1rem 0.5rem; font-size: 0.7rem; line-height: 1.2;" wire:click="eliminarBSE" title="Eliminar datos BSE">
                                    <i class="fas fa-trash-alt mr-1"></i>Borrar BSE
                                </button>
                                @endif
                            </legend>
                            
                            <div class="form-row px-2">
                                <div class="form-group col-md-6 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Nro. Ingreso BSE</label>
                                    <input type="text" class="form-control form-control-sm @error('pago.ingresoPagosBSE') is-invalid @enderror" wire:model.defer="pago.ingresoPagosBSE" placeholder="Ingrese número BSE">
                                    @error('pago.ingresoPagosBSE') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group col-md-6 mb-1">
                                    <label class="small mb-1 font-weight-bold text-muted">Fecha BSE</label>
                                    <input type="date" class="form-control form-control-sm @error('pago.fechaIngresoBSEPagos') is-invalid @enderror" wire:model.defer="pago.fechaIngresoBSEPagos">
                                    @error('pago.fechaIngresoBSEPagos') <span class="invalid-feedback" style="font-size: 0.7rem;">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>
                        @endif

                        <div class="d-flex justify-content-end mt-3 pt-3 border-top">
                            <button type="button" class="btn btn-light btn-sm border mr-2" wire:click="cerrarModal">
                                Cancelar
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" wire:click="actualizarPago" wire:loading.attr="disabled" wire:target="actualizarPago">
                                <span wire:loading.class="d-none" wire:target="actualizarPago">
                                    <i class="fas fa-save mr-1"></i> Guardar
                                </span>
                                <span wire:loading.class.remove="d-none" wire:target="actualizarPago" class="spinner-border spinner-border-sm mr-2 d-none"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
