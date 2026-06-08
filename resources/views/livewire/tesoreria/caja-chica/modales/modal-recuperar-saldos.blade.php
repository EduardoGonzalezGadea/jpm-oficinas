<div>
    @if ($showModal)
    <div class="modal fade show" id="modalRecuperar" tabindex="-1" role="dialog" style="display: block;"
        aria-modal="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content modal-animate-in">
                <div class="modal-header modal-header-premium shadow-sm">
                    <h5 class="modal-title">
                        <i class="fas fa-undo-alt mr-3"></i>Recuperar Saldos Pendientes
                    </h5>
                    <button type="button" class="close" wire:click="cerrarModal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="recuperacion_fecha">Fecha de Recuperación *</label>
                            <input type="date" id="recuperacion_fecha"
                                class="form-control @error('recuperacion.fecha') is-invalid @enderror"
                                wire:model.defer="recuperacion.fecha">
                            @error('recuperacion.fecha')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="recuperacion_numero_ingreso">Número de Ingreso *</label>
                            <input type="text" id="recuperacion_numero_ingreso"
                                class="form-control @error('recuperacion.numero_ingreso') is-invalid @enderror"
                                wire:model.defer="recuperacion.numero_ingreso" placeholder="Ej: 12345">
                            @error('recuperacion.numero_ingreso')
                            <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="total_a_recuperar">Total a Recuperar</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="text" id="total_a_recuperar"
                                    class="form-control font-weight-bold text-right bg-light"
                                    value="{{ number_format($totalARecuperar, 2, ',', '.') }}"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" wire:loading.class="loading-overlay">
                        <table class="table table-sm table-bordered table-striped table-hover table-compact">
                            <thead>
                                <tr>
                                    <th width="5%" class="align-middle"><input type="checkbox" wire:model="seleccionarTodos">
                                    </th>
                                    <th class="align-middle">Tipo</th>
                                    <th class="align-middle">Detalle</th>
                                    <th class="text-right align-middle">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($itemsParaRecuperar as $item)
                                <tr wire:key="rec-item-{{ $item['id'] }}">
                                    <td><input type="checkbox" wire:model="itemsSeleccionados"
                                            value="{{ $item['id'] }}"></td>
                                    <td><span
                                            class="badge badge-{{ $item['tipo'] == 'Pendiente' ? 'info' : 'warning' }}">{{ $item['tipo'] }}</span>
                                    </td>
                                    <td>{{ $item['detalle'] }}</td>
                                    <td class="text-right">
                                        {{ number_format($item['saldo'], 2, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No hay ítems para recuperar.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Total a Recuperar:</th>
                                    <th class="text-right font-weight-bold">
                                        {{ number_format($totalARecuperar, 2, ',', '.') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                        @error('itemsSeleccionados')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" wire:click="cerrarModal">
                        <i class="fas fa-arrow-left mr-2"></i>Volver
                    </button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" wire:click="guardarRecuperacion"
                        wire:loading.attr="disabled">
                        <span wire:loading.class="d-none" wire:target="guardarRecuperacion">
                            <i class="fas fa-check-circle mr-2"></i>Guardar Recuperación
                        </span>
                        <span wire:loading wire:target="guardarRecuperacion" class="d-none">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Procesando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function() {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('modalRecuperar')) {
                Livewire.getByName('tesoreria.caja-chica.modales.modal-recuperar-saldos')[0].cerrarModal();
            }
        });
    });
</script>
@endpush
