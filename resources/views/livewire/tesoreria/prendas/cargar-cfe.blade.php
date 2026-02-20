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
            border-color: #e0a800;
            background-color: rgba(255, 193, 7, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.15);
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
        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background-color: #f0ad4e; color: #212529;">
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-upload mr-2"></i>Cargar CFE de Prenda</h5>
        </div>
        <div class="card-body py-3">
            <div class="upload-container">
                <div class="upload-box {{ $archivo ? 'has-file' : '' }}">
                    <input type="file" id="archivoPrenda" wire:model="archivo" accept=".pdf" wire:key="input-prenda-{{ $archivo ? 'loaded' : 'empty' }}">
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
                        <i class="fas fa-cloud-upload-alt" style="color: #e0a800;"></i>
                        <h6 class="font-weight-bold mb-1">Arrastra el CFE de Prenda aquí</h6>
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
    <div class="card border-success shadow-sm">
        <div class="card-header bg-success text-white py-1 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-check-double mr-2"></i>Datos Detectados de la Prenda</h6>
            <div class="small">
                <strong>{{ $datosExtraidos['serie'] }}-{{ $datosExtraidos['numero'] }}</strong>
            </div>
        </div>
        <div class="card-body p-3">
            {{-- Fila 1: Fecha, Nombre/Cédula, Monto --}}
            <div class="row mb-3">
                <div class="col-md-2">
                    <label class="text-muted small font-weight-bold mb-0">Fecha</label>
                    <div class="font-weight-bold">{{ $datosExtraidos['fecha'] }}</div>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small font-weight-bold mb-0">Nombre / Cédula</label>
                    <div class="font-weight-bold">{{ $datosExtraidos['nombre'] }}</div>
                    <small class="text-muted">{{ $datosExtraidos['cedula'] }}</small>
                </div>
                <div class="col-md-2">
                    <label class="text-muted small font-weight-bold mb-0">Teléfono</label>
                    <div class="font-weight-bold">{{ $datosExtraidos['telefono'] ?: '-' }}</div>
                </div>
                <div class="col-md-4 text-right">
                    <label class="text-muted small font-weight-bold mb-0 d-block">Monto Total CFE</label>
                    <div class="h3 text-success font-weight-bold mb-0">$ {{ $datosExtraidos['monto_total'] }}</div>
                </div>
            </div>

            {{-- Fila 2: Detalle --}}
            <div class="row mb-3 border-top pt-2">
                <div class="col-md-12">
                    <label class="text-muted small font-weight-bold d-block">Concepto / Detalle</label>
                    <div class="font-weight-bold text-uppercase">{{ $datosExtraidos['detalle'] }}</div>
                </div>
            </div>

            {{-- Fila 3: Orden de Cobro, Medio de Pago, Adenda --}}
            <div class="row mb-2 border-top pt-2">
                <div class="col-md-3">
                    <label class="text-muted small font-weight-bold d-block" style="color: #e0a800 !important;">
                        <i class="fas fa-hashtag mr-1"></i>Orden de Cobro
                    </label>
                    @if($datosExtraidos['orden_cobro'])
                    <div class="font-weight-bold h5 mb-0" style="color: #e0a800;">{{ $datosExtraidos['orden_cobro'] }}</div>
                    @else
                    <div class="text-muted font-italic small">No detectada</div>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="text-muted small font-weight-bold d-block text-primary">Medio de Pago</label>
                    <div class="font-weight-bold">{{ $datosExtraidos['forma_pago'] ?: 'SIN DATOS' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small font-weight-bold d-block">Adenda</label>
                    <div class="small" style="white-space: pre-line;">{{ $datosExtraidos['adenda'] ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="card-footer py-2 d-flex justify-content-end align-items-center">
            <button wire:click="limpiar" class="btn btn-secondary btn-sm mr-2 px-3">
                <i class="fas fa-times mr-1"></i> Cancelar
            </button>
            <button wire:click="guardarRegistro" wire:loading.attr="disabled" class="btn btn-warning btn-sm px-4 shadow-sm">
                <i class="fas fa-save mr-1"></i> Registrar Prenda
            </button>
        </div>
    </div>
    @endif
</div>