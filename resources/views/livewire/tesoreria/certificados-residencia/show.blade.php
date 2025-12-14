<div wire:ignore.self class="modal fade" id="showModal" tabindex="-1" role="dialog" aria-labelledby="showModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showModalLabel">Detalles del Certificado</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                @if($certificado)
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Datos del Titular</h5>
                            <p><strong>Nombre:</strong> {{ $certificado->titular_nombre }} {{ $certificado->titular_apellido }}</p>
                            <p><strong>Documento:</strong> {{ $certificado->titular_tipo_documento }} - {{ $certificado->titular_nro_documento }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Estado: <span class="badge badge-{{ $certificado->estado == 'Recibido' ? 'primary' : ($certificado->estado == 'Entregado' ? 'success' : 'danger') }}">{{ $certificado->estado }}</span></h5>
                            <p><strong>Fecha Recibido:</strong> {{ \Carbon\Carbon::parse($certificado->fecha_recibido)->format('d/m/Y') }}</p>
                            <p><strong>Recibido por:</strong> {{ $certificado->receptor->nombre }} {{ $certificado->receptor->apellido }}</p>
                        </div>
                    </div>

                    @if($certificado->estado == 'Entregado')
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Datos de Entrega</h5>
                                <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($certificado->fecha_entregado)->format('d/m/Y') }}</p>
                                <p><strong>Entregado por:</strong> {{ $certificado->entregador->nombre }} {{ $certificado->entregador->apellido }}</p>
                            </div>
                            <div class="col-md-6">
                                <h5>Datos de Quien Retira</h5>
                                <p><strong>Nombre:</strong> {{ $certificado->retira_nombre }} {{ $certificado->retira_apellido }}</p>
                                <p><strong>Documento:</strong> {{ $certificado->retira_tipo_documento }} - {{ $certificado->retira_nro_documento }}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Teléfono:</strong> {{ $certificado->retira_telefono ?? 'SIN DATO' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Número de Recibo:</strong> {{ $certificado->numero_recibo ?? 'SIN DATO' }}</p>
                            </div>
                        </div>
                    @elseif($certificado->estado == 'Devuelto')
                        <hr>
                        <h5>Datos de Devolución</h5>
                        <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($certificado->fecha_devuelto)->format('d/m/Y') }}</p>
                        <p><strong>Devuelto por:</strong> {{ $certificado->devolucionUser->nombre }} {{ $certificado->devolucionUser->apellido }}</p>
                    @endif

                    <hr>
                    <h5>Información de Auditoría</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Creado por:</strong> {{ $certificado->createdBy->nombre ?? 'N/A' }} {{ $certificado->createdBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Creación:</strong> {{ \Carbon\Carbon::parse($certificado->created_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última Modificación por:</strong> {{ $certificado->updatedBy->nombre ?? 'N/A' }} {{ $certificado->updatedBy->apellido ?? '' }}</p>
                            <p><strong>Fecha Modificación:</strong> {{ \Carbon\Carbon::parse($certificado->updated_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                        @if($certificado->deleted_at)
                            <div class="col-md-12">
                                <p><strong>Eliminado por:</strong> {{ $certificado->deletedBy->nombre ?? 'N/A' }} {{ $certificado->deletedBy->apellido ?? '' }}</p>
                                <p><strong>Fecha Eliminación:</strong> {{ \Carbon\Carbon::parse($certificado->deleted_at)->format('d/m/Y H:i:s') }}</p>
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