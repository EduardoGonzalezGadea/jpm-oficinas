<div wire:ignore.self class="modal fade" id="showModal" tabindex="-1" role="dialog" aria-labelledby="showModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showModalLabel">Detalles de la Prenda</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                @if($prenda)
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Serie Recibo:</strong> {{ $prenda->recibo_serie }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Número Recibo:</strong> {{ $prenda->recibo_numero }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fecha Recibo:</strong> {{ \Carbon\Carbon::parse($prenda->recibo_fecha)->format('d/m/Y') }}</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Orden de Cobro:</strong> {{ $prenda->orden_cobro }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Concepto:</strong> {{ $prenda->concepto }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Datos del Titular</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Nombre:</strong> {{ $prenda->titular_nombre }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Cédula:</strong> {{ $prenda->titular_cedula }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Teléfono:</strong> {{ $prenda->titular_telefono ?? 'Sin dato' }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Datos de Pago</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Monto:</strong> ${{ number_format($prenda->monto, 2, ',', '.') }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Medio de Pago:</strong> {{ $prenda->medioPago->nombre ?? 'Sin dato' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Transferencia:</strong> {{ $prenda->transferencia ?? 'Sin dato' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Fecha Transferencia:</strong> {{ $prenda->transferencia_fecha ? \Carbon\Carbon::parse($prenda->transferencia_fecha)->format('d/m/Y') : 'Sin dato' }}</p>
                        </div>
                    </div>

                    <hr>
                    <h5>Información de Auditoría</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Creado por:</strong> {{ $prenda->createdBy->nombre ?? 'Sin dato' }} {{ $prenda->createdBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Creación:</strong> {{ \Carbon\Carbon::parse($prenda->created_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última Modificación por:</strong> {{ $prenda->updatedBy->nombre ?? 'Sin dato' }} {{ $prenda->updatedBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Modificación:</strong> {{ \Carbon\Carbon::parse($prenda->updated_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        @if($prenda->deleted_at)
                            <div class="col-md-12">
                                <p><strong>Eliminado por:</strong> {{ $prenda->deletedBy->nombre ?? 'Sin dato' }} {{ $prenda->deletedBy->apellido ?? '' }}</p>
                                <p><strong>Fecha Eliminación:</strong> {{ \Carbon\Carbon::parse($prenda->deleted_at)->format('d/m/Y H:i:s') }}</p>
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
