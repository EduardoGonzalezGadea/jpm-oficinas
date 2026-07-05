<div>
    <h2>CFEs Pendientes de Confirmación</h2>

    @if($cfePendientes->isEmpty())
        <p>No hay CFEs pendientes.</p>
    @else
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo</th>
                        <th>Serie</th>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cfePendientes as $index => $cfe)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $cfe->tipo_cfe)) }}</td>
                            <td>{{ $cfe->serie }}</td>
                            <td>{{ $cfe->numero }}</td>
                            <td>{{ $cfe->fecha }}</td>
                            <td>{{ number_format($cfe->monto, 2, ',', '.') }}</td>
                            <td>
                                <span class="badge
                                    @if($cfe->estado == 'pendiente')
                                        text-bg-warning
                                    @elseif($cfe->estado == 'en_revision')
                                        text-bg-info
                                    @elseif($cfe->estado == 'confirmado')
                                        text-bg-success
                                    @elseif($cfe->estado == 'rechazado')
                                        text-bg-danger
                                    @elseif($cfe->estado == 'expirado')
                                        text-bg-secondary
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $cfe->estado)) }}
                                </span>
                            </td>
                            <td>
                                @if(in_array($cfe->estado, ['pendiente', 'en_revision']))
                                    <button
                                        wire:click="verDetalles({{ $cfe->id }})"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cfePendienteModal">
                                        Ver Detalles
                                    </button>
                                    <button
                                        wire:click="$emit('review-cfe', {{ $cfe->id }})"
                                        class="btn btn-sm btn-info ms-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reviewModal">
                                        Revisar y Confirmar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $cfePendientes->links() }}
    @endif
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="cfePendienteModal" tabindex="-1" aria-labelledby="cfePendienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cfePendienteModalLabel">Detalles del CFE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($cfePendienteToConfirm)
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> {{ $cfePendienteToConfirm->id }}</p>
                            <p><strong>Tipo:</strong> {{ ucfirst(str_replace('_', ' ', $cfePendienteToConfirm->tipo_cfe)) }}</p>
                            <p><strong>Serie:</strong> {{ $cfePendienteToConfirm->serie }}</p>
                            <p><strong>Número:</strong> {{ $cfePendienteToConfirm->numero }}</p>
                            <p><strong>Fecha:</strong> {{ $cfePendienteToConfirm->fecha }}</p>
                            <p><strong>Monto:</strong> {{ number_format($cfePendienteToConfirm->monto, 2, ',', '.') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> 
                                <span class="badge
                                    @if($cfePendienteToConfirm->estado == 'pendiente')
                                        text-bg-warning
                                    @elseif($cfePendienteToConfirm->estado == 'en_revision')
                                        text-bg-info
                                    @elseif($cfePendienteToConfirm->estado == 'confirmado')
                                        text-bg-success
                                    @elseif($cfePendienteToConfirm->estado == 'rechazado')
                                        text-bg-danger
                                    @elseif($cfePendienteToConfirm->estado == 'expirado')
                                        text-bg-secondary
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $cfePendienteToConfirm->estado)) }}
                                </span>
                            </p>
                            @if($cfePendienteToConfirm->motivo_rechazo)
                                <p><strong>Motivo de Rechazo:</strong> {{ $cfePendienteToConfirm->motivo_rechazo }}</p>
                            @endif
                            @if($cfePendienteToConfirm->procesado_por)
                                <p><strong>Procesado por:</strong> {{ $cfePendienteToConfirm->procesadoPor->name ?? $cfePendienteToConfirm->procesado_por }}</p>
                            @endif
                            @if($cfePendienteToConfirm->procesado_at)
                                <p><strong>Procesado el:</strong> {{ $cfePendienteToConfirm->procesado_at->format('d/m/Y H:i:s') }}</p>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <p><strong>Datos Extraídos:</strong></p>
                    <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow: auto;">{{ json_encode($cfePendienteToConfirm->datos_extraidos, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
            <div class="modal-footer">
                @if($cfePendienteToConfirm && in_array($cfePendienteToConfirm->estado, ['pendiente', 'en_revision']))
                    <button type="button" class="btn btn-success" wire:click="confirmarCfe">Confirmar y Crear CFE</button>
                    <button type="button" class="btn btn-info" wire:click="marcarEnRevision">Marcar En Revisión</button>
                    <button type="button" class="btn btn-danger" wire:click="abrirModalRechazo">Rechazar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                @else
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazo -->
<div class="modal fade" id="rechazoModal" tabindex="-1" aria-labelledby="rechazoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rechazoModalLabel">Rechazar CFE</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de rechazar este CFE?</p>
                <div class="mb-3">
                    <label for="motivoRechazo" class="form-label">Motivo de rechazo <span class="text-danger">*</span></label>
                    <textarea
                        wire:model="motivoRechazo"
                        id="motivoRechazo"
                        class="form-control"
                        rows="4"
                        placeholder="Ingrese el motivo del rechazo..."></textarea>
                    @error('motivoRechazo')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" wire:click="rechazarCfe">Confirmar Rechazo</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function() {
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

@livewire('tesoreria.cfe-pendientes.review')