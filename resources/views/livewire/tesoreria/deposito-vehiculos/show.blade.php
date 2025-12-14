<div wire:ignore.self class="modal fade" id="showModal" tabindex="-1" role="dialog" aria-labelledby="showModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showModalLabel">Detalles del Depósito de Vehículo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                @if($deposito)
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Serie Recibo:</strong> {{ $deposito->recibo_serie }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Número Recibo:</strong> {{ $deposito->recibo_numero }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fecha Recibo:</strong> {{ \Carbon\Carbon::parse($deposito->recibo_fecha)->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Orden de Cobro:</strong> {{ $deposito->orden_cobro }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Concepto:</strong> {{ $deposito->concepto }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Datos del Titular</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Titular:</strong> {{ $deposito->titular }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Cédula:</strong> {{ $deposito->cedula }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Teléfono:</strong> {{ $deposito->telefono ?? 'Sin dato' }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Datos de Pago</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Monto:</strong> <span class="text-nowrap">${{ number_format($deposito->monto, 2, ',', '.') }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Medio de Pago:</strong> {{ $deposito->medioPago->nombre ?? 'Sin dato' }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Información de Auditoría</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Creado por:</strong> {{ $deposito->createdBy->nombre ?? 'Sin dato' }} {{ $deposito->createdBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Creación:</strong> {{ \Carbon\Carbon::parse($deposito->created_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última Modificación por:</strong> {{ $deposito->updatedBy->nombre ?? 'Sin dato' }} {{ $deposito->updatedBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Modificación:</strong> {{ \Carbon\Carbon::parse($deposito->updated_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        @if($deposito->deleted_at)
                            <div class="col-md-12">
                                <p><strong>Eliminado por:</strong> {{ $deposito->deletedBy->nombre ?? 'Sin dato' }} {{ $deposito->deletedBy->apellido ?? '' }}</p>
                                <p><strong>Fecha Eliminación:</strong> {{ \Carbon\Carbon::parse($deposito->deleted_at)->format('d/m/Y H:i:s') }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
