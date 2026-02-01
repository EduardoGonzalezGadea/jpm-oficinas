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
            border-color: #007bff;
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

        /* Mejora de contraste para textos muted */
        .theme-dark .text-muted {
            color: #adb5bd !important;
        }

        /* Ajuste para badges en temas oscuros */
        .theme-dark .badge-light {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #f8f9fa !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Asegurar que el footer de la tarjeta no tenga fondo blanco fijo */
        .theme-dark .card-footer {
            background-color: rgba(255, 255, 255, 0.03) !important;
        }

        /* Bordes más visibles en temas oscuros */
        .theme-dark .border-top,
        .theme-dark .border-right {
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
    </style>

    <div class="card {{ $datosExtraidos ? 'mb-2' : 'mb-4' }}">
        <div class="card-header bg-primary text-white card-header-gradient py-1 px-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><strong><i class="fas fa-file-upload mr-2"></i>Cargar CFE</strong></h5>
        </div>
        <div class="card-body py-3">
            <div class="upload-container">
                <div class="upload-box {{ $archivo ? 'has-file' : '' }}">
                    <input type="file" id="archivoCfe" wire:model="archivo" accept=".pdf" wire:key="input-cfe-{{ $archivo ? 'loaded' : 'empty' }}">

                    <div wire:loading.style="display: flex" wire:target="archivo" class="upload-loading-overlay">
                        <div class="text-white text-center">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <div class="font-weight-bold">Procesando archivo...</div>
                        </div>
                    </div>

                    <div class="upload-content">
                        @if($archivo)
                        <i class="fas fa-file-pdf text-success"></i>
                        <h6 class="text-success font-weight-bold mb-1">{{ $archivo->getClientOriginalName() }}</h6>
                        <p class="text-muted small mb-0">PDF listo para procesar. Revisa los datos abajo.</p>
                        @else
                        <i class="fas fa-cloud-upload-alt text-primary"></i>
                        <h6 class="font-weight-bold mb-1">Arrastra el CFE aquí o haz clic para buscar</h6>
                        <p class="text-muted small mb-0">Solo archivos PDF de hasta 10MB</p>
                        @endif
                    </div>
                </div>

                @if (session()->has('message'))
                <div class="alert alert-success py-1 px-2 mt-2 mb-0 small alert-dismissible fade show text-center" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('message') }}
                    <button type="button" class="close py-1" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif

                @error('archivo')
                <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small text-center">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                </div>
                @enderror

                @if($mensajeError)
                <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small text-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $mensajeError }}
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($datosExtraidos)
    <div class="card border-success shadow-sm">
        <div class="card-header bg-success text-white py-1 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list-alt mr-2"></i>Datos Extraídos</h6>
            <div class="small">
                <span class="badge badge-light text-success">{{ $datosExtraidos['tipo_cfe'] }}</span>
                <strong>{{ $datosExtraidos['serie'] }}-{{ $datosExtraidos['numero'] }}</strong>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="row no-gutters">
                <div class="col-md-4 pr-2 border-right border-secondary">
                    <table class="table table-sm table-borderless mb-0 small">
                        <tbody>
                            <tr>
                                <th class="py-0" width="45%">Fecha:</th>
                                <td class="py-0">{{ $datosExtraidos['fecha'] }}</td>
                            </tr>
                            <tr>
                                <th class="py-0">O/C:</th>
                                <td class="py-0"><strong>{{ $datosExtraidos['orden_cobro'] ?: '-' }}</strong></td>
                            </tr>
                            <tr>
                                <th class="py-0">Ing. Cont.:</th>
                                <td class="py-0">{{ $datosExtraidos['ingreso_contabilidad'] ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th class="py-0">N° Trámite:</th>
                                <td class="py-0">{{ $datosExtraidos['tramite'] ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-5 px-2 border-right border-secondary">
                    <table class="table table-sm table-borderless mb-0 small">
                        <tbody>
                            <tr>
                                <th class="py-0" width="35%">Cédula:</th>
                                <td class="py-0">{{ $datosExtraidos['rut_receptor'] }}</td>
                            </tr>
                            <tr>
                                <th class="py-0">Nombre:</th>
                                <td class="py-0"><strong>{{ $datosExtraidos['razon_social_receptor'] }}</strong></td>
                            </tr>
                            <tr>
                                <th class="py-0">Teléf.:</th>
                                <td class="py-0">{{ $datosExtraidos['telefono'] ?: '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-3 pl-2">
                    <div class="text-right small">
                        <div class="d-flex justify-content-between"><span>Subtotal:</span><span>$ {{ number_format((float)str_replace(['.', ','], ['', '.'], $datosExtraidos['subtotal']), 2, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between text-muted"><span>IVA:</span><span>$ {{ number_format((float)str_replace(['.', ','], ['', '.'], $datosExtraidos['iva']), 2, ',', '.') }}</span></div>
                        <div class="d-flex justify-content-between border-top mt-1 pt-1">
                            <strong class="text-success">TOTAL:</strong>
                            <strong class="text-success h6 mb-0">$ {{ number_format((float)str_replace(['.', ','], ['', '.'], $datosExtraidos['monto_total']), 2, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-2 pt-1 border-top small text-muted">
                <i class="fas fa-info-circle mr-1"></i><strong>Detalle:</strong> {{ $datosExtraidos['detalle'] }}
            </div>
        </div>
        <div class="card-footer py-1 d-flex justify-content-end">
            <button wire:click="limpiar" class="btn btn-secondary btn-sm mr-2 py-0">
                <i class="fas fa-broom mr-1"></i> Limpiar
            </button>
            <button wire:click="guardarRegistro" wire:loading.attr="disabled" class="btn btn-primary btn-sm py-0">
                <i class="fas fa-save mr-1"></i> Guardar Registro
            </button>
        </div>
    </div>
    @endif
</div>