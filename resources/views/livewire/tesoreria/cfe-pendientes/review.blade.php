<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true" wire:ignore>
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="fas fa-search-plus me-2"></i>Revisar y Confirmar CFE
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($pendiente)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Información del PDF</h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th style="width: 150px;">Tipo CFE</th>
                                    <td>{{ ucfirst(str_replace('_', ' ', $pendiente->tipo_cfe)) }}</td>
                                </tr>
                                <tr>
                                    <th>Serie</th>
                                    <td>{{ $pendiente->serie ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Número</th>
                                    <td>{{ $pendiente->numero ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Fecha</th>
                                    <td>{{ $pendiente->fecha ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Monto</th>
                                    <td>{{ number_format($pendiente->monto, 2, ',', '.') }} {{ $pendiente->moneda }}</td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        <span class="badge
                                            @if($pendiente->estado == 'pendiente')
                                                bg-warning text-dark
                                            @elseif($pendiente->estado == 'en_revision')
                                                bg-info
                                            @elseif($pendiente->estado == 'confirmado')
                                                bg-success
                                            @elseif($pendiente->estado == 'rechazado')
                                                bg-danger
                                            @else
                                                bg-secondary
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $pendiente->estado)) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Datos extraídos (editable)</h6>
                            <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                @foreach($datosEditados as $key => $value)
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small text-muted">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </label>
                                        @if(is_array($value))
                                            <textarea class="form-control form-control-sm" rows="3" disabled>{{ json_encode($value, JSON_PRETTY_PRINT) }}</textarea>
                                        @else
                                            <input type="text" class="form-control form-control-sm"
                                                   wire:model.debounce.300ms="datosEditados.{{ $key }}"
                                                   value="{{ is_string($value) ? $value : (is_numeric($value) ? number_format($value, 2, ',', '.') : $value) }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="text-muted">Acciones</h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" wire:click="confirmar">
                            <i class="fas fa-check me-1"></i>Confirmar y Crear CFE
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                    </div>
                @else
                    <p class="text-center text-muted">Cargando...</p>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function() {
        Livewire.on('show-modal', (data) => {
            const modal = new bootstrap.Modal(document.getElementById(data.id));
            modal.show();
        });

        Livewire.on('cerrar-modal', (data) => {
            const modalEl = document.getElementById(data.id);
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        });
    });
</script>