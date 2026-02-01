<div class="@if(in_array(auth()->user()->theme, ['cyborg', 'darkly', 'slate', 'solar', 'superhero'])) theme-dark @endif">
    <style>
        .upload-container {
            position: relative;
            transition: all 0.3s ease;
        }

        .upload-box {
            border: 2px dashed #ced4da;
            border-radius: 8px;
            background-color: rgba(128, 128, 128, 0.08);
            padding: 0.8rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .upload-box:hover {
            border-color: #007bff;
            background-color: rgba(0, 123, 255, 0.05);
        }

        .upload-box.has-file {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }

        .extra-small {
            font-size: 0.75rem;
            letter-spacing: 0.01rem;
        }

        .upload-box i {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
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
            backdrop-filter: blur(2px);
            display: none !important;
            align-items: center;
            justify-content: center;
            z-index: 20;
            border-radius: 8px;
        }

        /* Mejora de contraste para textos muted en temas oscuros */
        .theme-dark .text-muted,
        .theme-dark .text-adaptive-muted {
            color: #adb5bd !important;
            opacity: 1;
        }

        /* Ajuste para badges en temas oscuros */
        .theme-dark .badge-light {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #f8f9fa !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Bordes más visibles en temas oscuros */
        .theme-dark .border-top,
        .theme-dark .border-right {
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
    </style>

    <div class="card {{ $datosExtraidos ? 'mb-1' : 'mb-3' }}">
        <div class="card-header bg-info text-white card-header-gradient py-1 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice-dollar mr-2"></i>Cargar eFactura de Eventual</h6>
        </div>
        <div class="card-body p-2">
            <div class="upload-container">
                <div class="upload-box {{ $archivo ? 'has-file' : '' }}">
                    <input type="file" id="archivoEfactura" wire:model="archivo" accept=".pdf" wire:key="input-efactura-{{ $archivo ? 'loaded' : 'empty' }}">

                    <div wire:loading.style="display: flex" wire:target="archivo" class="upload-loading-overlay">
                        <div class="text-primary text-center">
                            <i class="fas fa-spinner fa-spin fa-lg mb-1"></i>
                            <div class="small font-weight-bold">Procesando...</div>
                        </div>
                    </div>

                    <div class="upload-content">
                        @if($archivo)
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-file-invoice text-success mr-2 mb-0"></i>
                            <div>
                                <span class="text-success font-weight-bold small">{{ $archivo->getClientOriginalName() }}</span>
                                <span class="text-adaptive-muted extra-small d-block">PDF listo para procesar</span>
                            </div>
                        </div>
                        @else
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-file-pdf text-info mr-2 mb-0"></i>
                            <div>
                                <span class="font-weight-bold small">Arrastra la eFactura o haz clic</span>
                                <span class="text-adaptive-muted extra-small d-block">Solo archivos PDF de hasta 10MB</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                @if (session()->has('message'))
                <div class="alert alert-success py-1 px-2 mt-1 mb-0 small alert-dismissible fade show text-center" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('message') }}
                    <button type="button" class="close py-1" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                @endif

                @error('archivo')
                <div class="alert alert-danger py-1 px-2 mt-1 mb-0 small text-center">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                </div>
                @enderror

                @if($mensajeError)
                <div class="alert alert-danger py-1 px-2 mt-1 mb-0 small text-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $mensajeError }}
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($datosExtraidos)
    <div class="card border-info shadow-sm mb-0">
        <div class="card-header bg-info text-white py-1 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 small font-weight-bold"><i class="fas fa-list-alt mr-2"></i>Datos Extraídos</h6>
            <div class="small">
                <span class="badge badge-light text-info">Recibo: {{ $datosExtraidos['recibo'] }}</span>
            </div>
        </div>
        <div class="card-body p-2">
            <div class="row no-gutters">
                <div class="col-md-5 pr-2 border-right">
                    <table class="table table-sm table-borderless mb-0 extra-small">
                        <tbody>
                            <tr>
                                <th width="35%">Titular:</th>
                                <td><strong>{{ $datosExtraidos['titular'] }}</strong></td>
                            </tr>
                            <tr>
                                <th>Fecha:</th>
                                <td>{{ $datosExtraidos['fecha'] }}</td>
                            </tr>
                            <tr>
                                <th>O/C:</th>
                                <td><strong>{{ $datosExtraidos['orden_cobro'] ?: '-' }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4 px-2 border-right">
                    <table class="table table-sm table-borderless mb-0 extra-small">
                        <tbody>
                            <tr>
                                <th width="45%">M. Pago:</th>
                                <td><strong>{{ $datosExtraidos['medio_de_pago'] ?: 'TRANSFERENCIA' }}</strong></td>
                            </tr>
                            <tr>
                                <th>Monto:</th>
                                <td><strong class="text-success" style="font-size: 0.9rem;">$ {{ number_format((float)str_replace(['.', ','], ['', '.'], $datosExtraidos['monto']), 2, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-3 pl-2 d-flex align-items-center justify-content-center">
                    <div class="btn-group-vertical btn-group-sm w-100">
                        <button wire:click="guardar" wire:loading.attr="disabled" class="btn btn-primary btn-sm mb-1">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                        <button wire:click="limpiar" class="btn btn-secondary btn-sm">
                            <i class="fas fa-broom mr-1"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-1 pt-1 border-top extra-small">
                <strong><i class="fas fa-info-circle mr-1"></i>Detalle:</strong>
                <span class="text-adaptive-muted">{{ $datosExtraidos['detalle'] }}</span>
            </div>
        </div>
    </div>
    @endif
</div>
</div>
</div>