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
                                        text-warning
                                    @elseif($cfe->estado == 'confirmado')
                                        text-success
                                    @elseif($cfe->estado == 'rechazado')
                                        text-danger
                                    @endif">
                                    {{ ucfirst($cfe->estado) }}
                                </span>
                            </td>
                            <td>
                                @if($cfe->estado == 'pendiente')
                                    <button
                                        wire:click="verDetalles({{ $cfe->id }})"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cfePendienteModal">
                                        Ver Detalles
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="cfePendienteModal" tabindex="-1" aria-labelledby="cfePendienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cfePendienteModalLabel">Detalles del CFE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($cfePendienteToConfirm)
                    <p><strong>ID:</strong> {{ $cfePendienteToConfirm->id }}</p>
                    <p><strong>Tipo:</strong> {{ ucfirst(str_replace('_', ' ', $cfePendienteToConfirm->tipo_cfe)) }}</p>
                    <p><strong>Serie:</strong> {{ $cfePendienteToConfirm->serie }}</p>
                    <p><strong>Número:</strong> {{ $cfePendienteToConfirm->numero }}</p>
                    <p><strong>Fecha:</strong> {{ $cfePendienteToConfirm->fecha }}</p>
                    <p><strong>Monto:</strong> {{ number_format($cfePendienteToConfirm->monto, 2, ',', '.') }}</p>
                    <p><strong>Estado:</strong> {{ ucfirst($cfePendienteToConfirm->estado) }}</p>
                    <p><strong>Motivo de Rechazo:</strong> {{ $cfePendienteToConfirm->motivo_rechazo }}</p>
                    <p><strong>Datos Extraídos:</strong></p>
                    <pre>{{ json_encode($cfePendienteToConfirm->datos_extraidos, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
            <div class="modal-footer">
                @if($cfePendienteToConfirm)
                    @if($cfePendienteToConfirm->estado == 'pendiente')
                        <button type="button" class="btn btn-success" wire:click="confirmarCfe">Confirmar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    @else
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    @endif
                @endif
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
