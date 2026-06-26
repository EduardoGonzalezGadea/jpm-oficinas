<div class="modal fade" id="modalEditarCfe" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
<div class="modal-dialog modal-full-width" role="document">
    <div class="modal-content border-0 shadow">

        <div class="modal-header bg-warning text-dark p-2">
            <h5 class="modal-title m-0">
                <i class="fas fa-edit mr-2"></i>
                <strong>Editar CFE</strong>
                @if($editDocumentoTipo)
                    <span class="badge badge-info ml-2">{{ $editDocumentoTipo }} {{ $editDocumentoSerie }}-{{ $editDocumentoNumero }}</span>
                @endif
            </h5>
            <button type="button" class="close" aria-label="Close"
                wire:click="cancelarEdicion">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="modal-body p-3">

            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-calendar mr-1 text-primary"></i> Fecha del Documento
                    </h6>
                    <div class="form-group mb-0">
                        <input type="date" class="form-control @error('editFecha') is-invalid @enderror"
                            wire:model="editFecha">
                        @error('editFecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-tag mr-1 text-primary"></i> Concepto de Caja
                    </h6>
                    <div class="form-group mb-0">
                        <select wire:model="editCajaConceptoSeleccionado"
                            class="form-control @error('editCajaConceptoSeleccionado') is-invalid @enderror">
                            <option value="">— Seleccione concepto —</option>
                            @foreach($cajaConceptos as $concepto)
                                <option value="{{ $concepto->id }}">{{ $concepto->caja_concepto }}</option>
                            @endforeach
                        </select>
                        @error('editCajaConceptoSeleccionado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="text-uppercase small font-weight-bold mb-2">
                        <i class="fas fa-sitemap mr-1 text-primary"></i> Dependencia
                    </h6>
                    <div class="form-group mb-0">
                        <select wire:model="editSiifDependenciaSeleccionado"
                            class="form-control @error('editSiifDependenciaSeleccionado') is-invalid @enderror">
                            <option value="">— Seleccione dep. SIIF —</option>
                            @foreach($siifDependencias as $dep)
                                <option value="{{ $dep->id }}">{{ $dep->abreviatura }} - {{ $dep->dependencia }}
                                </option>
                            @endforeach
                        </select>
                        @error('editSiifDependenciaSeleccionado')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <h6 class="text-uppercase small font-weight-bold mb-1 mt-3 border-bottom pb-1">
                <i class="fas fa-list mr-1"></i> Ítems
            </h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Detalle</th>
                            <th class="text-right" style="width:18%">Importe</th>
                            <th style="width:35%">Distribución SIIF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($editCfeItems as $index => $item)
                            <tr>
                                <td>
                                    {{ $item['detalle'] ?? '' }}
                                    @if(!empty($item['descripcion']))
                                        <br><small>{{ $item['descripcion'] }}</small>
                                    @endif
                                </td>
                                <td class="text-right align-middle font-weight-bold">
                                    $ {{ number_format($item['importe'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="align-middle">
                                    @if($editCajaConceptoSeleccionado && $editSiifDependenciaSeleccionado)
                                        <select wire:model="editItemDistribuciones.{{ $index }}"
                                            class="form-control form-control-sm @error('editItemDistribuciones.'.$index) is-invalid @enderror">
                                            <option value="">— Sin asignar —</option>
                                            @foreach($editDistribuciones as $dist)
                                                <option value="{{ $dist->id }}">{{ $dist->concepto }}</option>
                                            @endforeach
                                        </select>
                                        @error('editItemDistribuciones.'.$index)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <span class="small text-info">
                                            <i class="fas fa-info-circle mr-1"></i> Seleccione concepto y dependencia
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-2 text-muted small">Este CFE no tiene ítems.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary" wire:click="cancelarEdicion">
                <i class="fas fa-times mr-1"></i> Cancelar
            </button>
            <button type="button" class="btn btn-warning" wire:click="guardarEdicion"
                wire:loading.attr="disabled" wire:target="guardarEdicion">
                <span wire:loading.remove wire:target="guardarEdicion">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </span>
                <span wire:loading wire:target="guardarEdicion">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Guardando...
                </span>
            </button>
        </div>

    </div>
</div>
</div>
