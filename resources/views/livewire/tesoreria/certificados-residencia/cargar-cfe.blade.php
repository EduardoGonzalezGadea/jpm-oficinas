<div class="@if(in_array(auth()->user()->theme, ['cyborg', 'darkly', 'slate', 'solar', 'superhero'])) theme-dark @endif">
    <style>
        .upload-container {
            position: relative;
            transition: all 0.3s ease;
        }

        .upload-box {
            border: 2px dashed #ced4da;
            border-radius: 10px;
            background-color: rgba(128, 128, 128, 0.05);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .upload-box:hover {
            border-color: #17a2b8;
            background-color: rgba(128, 128, 128, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .upload-box.has-file {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }

        .upload-box i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        .upload-box:hover i {
            transform: scale(1.1);
        }

        .upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }

        .upload-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: none !important;
            align-items: center;
            justify-content: center;
            z-index: 20;
            border-radius: 8px;
        }

        .cert-card {
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .cert-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .cert-card.selected {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }

        .cert-card.selected::before {
            content: '\f058';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 8px;
            right: 12px;
            color: #28a745;
            font-size: 1.2rem;
        }

        .theme-dark .text-muted {
            color: #adb5bd !important;
        }

        .theme-dark .card-footer {
            background-color: rgba(255, 255, 255, 0.03) !important;
        }

        .theme-dark .border-top,
        .theme-dark .border-right {
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
    </style>

    {{-- Card de Carga de PDF --}}
    <div class="card {{ $datosExtraidos ? 'mb-2' : 'mb-4' }} shadow-sm">
        <div class="card-header bg-info text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-upload mr-2"></i>Cargar CFE de Certificado de Residencia</h5>
        </div>
        <div class="card-body py-3">
            <div class="upload-container">
                <div class="upload-box {{ $archivo ? 'has-file' : '' }}">
                    <input type="file" id="archivoCertificado" wire:model="archivo" accept=".pdf" wire:key="input-cert-{{ $archivo ? 'loaded' : 'empty' }}">
                    <div wire:loading.style="display: flex" wire:target="archivo" class="upload-loading-overlay">
                        <div class="text-white text-center">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <div class="font-weight-bold text-uppercase small">Procesando...</div>
                        </div>
                    </div>
                    <div class="upload-content">
                        @if($archivo)
                        <i class="fas fa-file-pdf text-success"></i>
                        <h6 class="text-success font-weight-bold mb-1">{{ $archivo->getClientOriginalName() }}</h6>
                        @else
                        <i class="fas fa-cloud-upload-alt text-info"></i>
                        <h6 class="font-weight-bold mb-1">Arrastra el CFE del Certificado aquí</h6>
                        <p class="text-muted small mb-0">Haz clic para buscar el archivo PDF</p>
                        @endif
                    </div>
                </div>

                @if($mensajeError)
                <div class="alert alert-danger py-2 px-3 mt-2 mb-0 small text-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $mensajeError }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Datos Extraídos del CFE --}}
    @if($datosExtraidos)
    <div class="card border-info shadow-sm mb-2">
        <div class="card-header bg-info text-white py-1 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-check-double mr-2"></i>Datos del CFE</h6>
            <div class="small">
                <strong>{{ $datosExtraidos['serie'] }}-{{ $datosExtraidos['numero'] }}</strong>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="text-muted small font-weight-bold mb-0">Fecha</label>
                            <div class="font-weight-bold">{{ $datosExtraidos['fecha'] }}</div>
                        </div>
                        <div class="col-md-5">
                            <label class="text-muted small font-weight-bold mb-0">
                                @if($datosExtraidos['retira_es_titular'])
                                Titular / Quien Retira
                                @else
                                Quien Retira (No es el titular)
                                @endif
                            </label>
                            <div class="font-weight-bold">{{ $datosExtraidos['nombre_receptor'] }}</div>
                            <small class="text-muted">C.I.: {{ $datosExtraidos['cedula_receptor'] }}</small>
                        </div>
                        <div class="col-md-4">
                            @if(!$datosExtraidos['retira_es_titular'])
                            <label class="text-muted small font-weight-bold mb-0">
                                <i class="fas fa-user-tag text-warning mr-1"></i>CI Titular (del certificado)
                            </label>
                            <div class="font-weight-bold text-warning">{{ $datosExtraidos['cedula_titular'] }}</div>
                            <small class="text-muted font-italic">Detectada en descripción</small>
                            @else
                            <label class="text-muted small font-weight-bold mb-0">Teléfono</label>
                            <div class="font-weight-bold">{{ $datosExtraidos['telefono'] ?: '-' }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <label class="text-muted small font-weight-bold mb-0 d-block">Monto Total CFE</label>
                    <div class="h3 text-success font-weight-bold mb-0">$ {{ $datosExtraidos['monto_total'] }}</div>
                </div>
            </div>

            <div class="row mb-2 border-top pt-2">
                <div class="col-md-6">
                    <label class="text-muted small font-weight-bold d-block">Detalle</label>
                    <div class="font-weight-bold text-uppercase">{{ $datosExtraidos['detalle'] }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small font-weight-bold d-block">Descripción</label>
                    <div class="small">{{ $datosExtraidos['descripcion'] ?: '-' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small font-weight-bold d-block text-primary">Medio de Pago</label>
                    <div class="font-weight-bold">{{ $datosExtraidos['forma_pago'] ?: 'SIN DATOS' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Certificados Encontrados --}}
    <div class="card shadow-sm mb-2">
        <div class="card-header py-2 px-3 {{ count($certificadosEncontrados) > 0 ? 'bg-success text-white' : 'bg-warning text-dark' }}">
            <h6 class="mb-0 font-weight-bold">
                @if(count($certificadosEncontrados) > 0)
                <i class="fas fa-search mr-2"></i>
                {{ count($certificadosEncontrados) }} Certificado(s) Pendiente(s) Encontrado(s)
                <small class="ml-2">(CI: {{ $datosExtraidos['cedula_titular'] }})</small>
                @else
                <i class="fas fa-exclamation-triangle mr-2"></i>
                No se encontraron certificados pendientes para la CI: {{ $datosExtraidos['cedula_titular'] }}
                @endif
            </h6>
        </div>

        @if(count($certificadosEncontrados) > 0)
        <div class="card-body p-3">
            <p class="text-muted small mb-2">
                <i class="fas fa-info-circle mr-1"></i>
                Selecciona el certificado que deseas marcar como entregado:
            </p>
            <div class="row">
                @foreach($certificadosEncontrados as $cert)
                <div class="col-md-{{ count($certificadosEncontrados) == 1 ? '12' : '6' }} mb-2">
                    <div
                        class="card cert-card position-relative {{ $certificadoSeleccionadoId == $cert['id'] ? 'selected' : '' }}"
                        wire:click="seleccionarCertificado({{ $cert['id'] }})">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-bold">
                                        {{ $cert['titular_nombre'] }} {{ $cert['titular_apellido'] }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $cert['titular_tipo_documento'] }}: {{ $cert['titular_nro_documento'] }}
                                    </small>
                                </div>
                                <div class="text-right">
                                    <div class="small text-muted">Recibido:</div>
                                    <div class="font-weight-bold">{{ \Carbon\Carbon::parse($cert['fecha_recibido'])->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="card-body py-4 text-center">
            <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
            <p class="text-muted mb-0">No hay certificados en estado <strong>"Recibido"</strong> que coincidan con la cédula <strong>{{ $datosExtraidos['cedula_titular'] }}</strong>.</p>
            <p class="text-muted small">Verifica que el certificado haya sido registrado previamente como recibido.</p>
        </div>
        @endif
    </div>

    {{-- Resumen de Entrega y Botones --}}
    @if($certificadoSeleccionadoId && count($certificadosEncontrados) > 0)
    @php
    $certSeleccionado = collect($certificadosEncontrados)->firstWhere('id', $certificadoSeleccionadoId);
    @endphp
    <div class="card border-success shadow-sm">
        <div class="card-header bg-success text-white py-2 px-3">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-clipboard-check mr-2"></i>Resumen de Entrega</h6>
        </div>
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light border mb-0">
                        <div class="card-body py-2 px-3">
                            <small class="text-muted font-weight-bold text-uppercase d-block" style="font-size: 0.7rem;">Certificado del Titular</small>
                            <div class="font-weight-bold">{{ $certSeleccionado['titular_nombre'] }} {{ $certSeleccionado['titular_apellido'] }}</div>
                            <small class="text-muted">{{ $certSeleccionado['titular_tipo_documento'] }}: {{ $certSeleccionado['titular_nro_documento'] }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light border mb-0">
                        <div class="card-body py-2 px-3">
                            <small class="text-muted font-weight-bold text-uppercase d-block" style="font-size: 0.7rem;">Retira</small>
                            <div class="font-weight-bold">{{ $datosExtraidos['nombre_receptor'] }}</div>
                            <small class="text-muted">C.I.: {{ $datosExtraidos['cedula_receptor'] }}</small>
                            @if(!$datosExtraidos['retira_es_titular'])
                            <span class="badge badge-warning ml-2">No es el titular</span>
                            @else
                            <span class="badge badge-success ml-2">Es el titular</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <small class="text-muted font-weight-bold">Fecha entrega:</small>
                    <span class="font-weight-bold ml-1">{{ $datosExtraidos['fecha'] }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted font-weight-bold">Recibo:</small>
                    <span class="font-weight-bold ml-1">{{ $datosExtraidos['serie'] }}-{{ $datosExtraidos['numero'] }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted font-weight-bold">Teléfono:</small>
                    <span class="font-weight-bold ml-1">{{ $datosExtraidos['telefono'] ?: '-' }}</span>
                </div>
            </div>
        </div>
        <div class="card-footer py-2 d-flex justify-content-end align-items-center">
            <button wire:click="limpiar" class="btn btn-secondary btn-sm mr-2 px-3">
                <i class="fas fa-times mr-1"></i> Cancelar
            </button>
            <button wire:click="confirmarEntrega" wire:loading.attr="disabled" class="btn btn-success btn-sm px-4 shadow-sm">
                <i class="fas fa-check-double mr-1"></i> Confirmar Entrega del Certificado
            </button>
        </div>
    </div>
    @elseif(count($certificadosEncontrados) > 0)
    <div class="card-footer py-2 d-flex justify-content-end align-items-center">
        <button wire:click="limpiar" class="btn btn-secondary btn-sm px-3">
            <i class="fas fa-times mr-1"></i> Cancelar
        </button>
    </div>
    @else
    <div class="d-flex justify-content-end mt-2">
        <button wire:click="limpiar" class="btn btn-secondary btn-sm px-3">
            <i class="fas fa-times mr-1"></i> Cancelar y Volver
        </button>
    </div>
    @endif
    @endif
</div>