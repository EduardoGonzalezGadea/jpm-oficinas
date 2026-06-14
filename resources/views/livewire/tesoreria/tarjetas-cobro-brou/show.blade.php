<div wire:ignore.self class="modal fade" id="showModal" tabindex="-1" role="dialog" aria-labelledby="showModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showModalLabel">Detalles de la Tarjeta de Cobro BROU</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                @if($tarjeta)
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Datos del Titular</h5>
                            <p><strong>Nombre:</strong> {{ $tarjeta->titular_nombre }} {{ $tarjeta->titular_apellido }}</p>
                            <p><strong>Cédula:</strong> {{ $tarjeta->titular_cedula }}</p>
                            <p><strong>Nro. Tarjeta:</strong> {{ $tarjeta->numero_tarjeta }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Estado: <span class="badge badge-{{ $tarjeta->estado == 'Recibido' ? 'primary' : ($tarjeta->estado == 'Entregado' ? 'success' : 'danger') }}">{{ $tarjeta->estado }}</span></h5>
                            <p><strong>Fecha Recibido:</strong> {{ \Carbon\Carbon::parse($tarjeta->fecha_recibido)->format('d/m/Y') }}</p>
                            <p><strong>Recibido por:</strong> {{ $tarjeta->receptor->nombre }} {{ $tarjeta->receptor->apellido }}</p>
                        </div>
                    </div>

                    @if($tarjeta->observaciones)
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Observaciones</h5>
                                <p>{{ $tarjeta->observaciones }}</p>
                            </div>
                        </div>
                    @endif

                    @if($tarjeta->estado == 'Entregado')
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Datos de Entrega</h5>
                                <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($tarjeta->fecha_entregado)->format('d/m/Y') }}</p>
                                <p><strong>Entregado por:</strong> {{ $tarjeta->entregador->nombre }} {{ $tarjeta->entregador->apellido }}</p>
                            </div>
                        </div>
                    @elseif($tarjeta->estado == 'Devuelto')
                        <hr>
                        <h5>Datos de Devolución</h5>
                        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($tarjeta->fecha_devuelto)->format('d/m/Y') }}</p>
                        <p><strong>Devuelto por:</strong> {{ $tarjeta->devolucionUser->nombre }} {{ $tarjeta->devolucionUser->apellido }}</p>
                    @endif

                    <hr>
                    <h5>Información de Auditoría</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Creado por:</strong> {{ $tarjeta->createdBy->nombre ?? 'N/A' }} {{ $tarjeta->createdBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Creación:</strong> {{ \Carbon\Carbon::parse($tarjeta->created_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última Modificación por:</strong> {{ $tarjeta->updatedBy->nombre ?? 'N/A' }} {{ $tarjeta->updatedBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Modificación:</strong> {{ \Carbon\Carbon::parse($tarjeta->updated_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        @if($tarjeta->deleted_at)
                            <div class="col-md-12">
                                <p><strong>Eliminado por:</strong> {{ $tarjeta->deletedBy->nombre ?? 'N/A' }} {{ $tarjeta->deletedBy->apellido ?? '' }}</p>
                                <p><strong>Fecha Eliminación:</strong> {{ \Carbon\Carbon::parse($tarjeta->deleted_at)->format('d/m/Y H:i:s') }}</p>
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