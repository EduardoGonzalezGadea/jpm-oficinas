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
            border-color: #dc3545;
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

    <div class="card {{ $datosExtraidos ? 'mb-2' : 'mb-4' }} shadow-sm">
        <div class="card-header bg-danger text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-upload mr-2"></i>Cargar CFE de Multa</h5>
        </div>
        <div class="card-body py-3">
            <div class="upload-container">
                <div class="upload-box {{ $archivo ? 'has-file' : '' }}">
                    <input type="file" id="archivoMulta" wire:model="archivo" accept=".pdf" wire:key="input-multa-{{ $archivo ? 'loaded' : 'empty' }}">
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
                        <i class="fas fa-cloud-upload-alt text-danger"></i>
                        <h6 class="font-weight-bold mb-1">Arrastra el CFE de Multa aquí</h6>
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

    @if($datosExtraidos)
    <div class="card border-success shadow-sm">
        <div class="card-header bg-success text-white py-1 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 font-weight-bold"><i class="fas fa-check-double mr-2"></i>Datos Detectados</h6>
            <div class="small">
                <strong>{{ $datosExtraidos['serie'] }}-{{ $datosExtraidos['numero'] }}</strong>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-3"><label class="text-muted small font-weight-bold mb-0">Fecha</label>
                            <div class="font-weight-bold">{{ $datosExtraidos['fecha'] }}</div>
                        </div>
                        <div class="col-md-9"><label class="text-muted small font-weight-bold mb-0">Nombre / Cédula</label>
                            <div class="font-weight-bold">{{ $datosExtraidos['nombre'] }}</div><small class="text-muted">{{ $datosExtraidos['cedula'] }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <label class="text-muted small font-weight-bold mb-0 d-block">Monto Total CFE</label>
                    <div class="h3 text-success font-weight-bold mb-0">$ {{ $datosExtraidos['monto_total'] }}</div>
                </div>
            </div>

            <div class="row mb-3 border-top pt-2">
                <div class="col-md-12">
                    <label class="text-upper small text-muted font-weight-bold d-block text-primary">Medio de Pago Detectado</label>
                    <div class="font-weight-bold">{{ $datosExtraidos['forma_pago'] ?: 'SIN DATOS' }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="bg-light small">
                        <tr>
                            <th>Ítem / Concepto Detectado</th>
                            <th>Descripción / Boleta</th>
                            <th class="text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datosExtraidos['items'] as $item)
                        <tr>
                            <td class="font-weight-bold small text-uppercase">{{ $item['detalle'] }}</td>
                            <td class="small text-muted">{{ $item['descripcion'] }}</td>
                            <td class="text-right font-weight-bold">$ {{ number_format($item['importe'], 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <th colspan="2" class="text-right">Suma de ítems:</th>
                            <th class="text-right text-success">$ {{ number_format(array_sum(array_column($datosExtraidos['items'], 'importe')), 2, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer py-2 d-flex justify-content-end align-items-center">
            <button wire:click="limpiar" class="btn btn-secondary btn-sm mr-2 px-3">
                <i class="fas fa-times mr-1"></i> Cancelar
            </button>
            <button wire:click="guardarRegistro" wire:loading.attr="disabled" class="btn btn-primary btn-sm px-4 shadow-sm">
                <i class="fas fa-save mr-1"></i> Confirmar y Guardar Todos los Ítems
            </button>
        </div>
    </div>
    @endif
</div>