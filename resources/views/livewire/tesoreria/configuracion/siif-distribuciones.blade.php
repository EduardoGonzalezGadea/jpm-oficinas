<div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header card-header-section card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><strong><i class="fas fa-project-diagram mr-2"></i>Distribuciones SIIF</strong></h4>
                    <button type="button" class="btn btn-primary" wire:click.prevent="create">
                        <i class="fas fa-plus"></i> Nueva Distribución
                    </button>
                </div>
                <div class="card-body px-2">
                    {{-- Barra de filtros --}}
                    <div class="d-flex mb-3 align-items-center flex-wrap">
                        <div class="flex-grow-1 mr-2" style="max-width: 35%;">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" wire:model="search" class="form-control"
                                    placeholder="Buscar por concepto, rubro, código SIR...">
                            </div>
                        </div>
                        <div class="mr-2" style="width: 220px;">
                            <select wire:model="filtroTipo" class="form-control form-control-sm">
                                <option value="">— Todos los tipos —</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->tipo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mr-2" style="width: 220px;">
                            <select wire:model="filtroDependencia" class="form-control form-control-sm">
                                <option value="">— Todas las dependencias —</option>
                                @foreach($dependencias as $dep)
                                    <option value="{{ $dep->id }}">{{ $dep->dependencia }} ({{ $dep->abreviatura }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="text-nowrap">
                            <small class="font-weight-bold text-secondary">{{ $distribuciones->total() }} registros</small>
                        </div>
                        @if($search !== '' || $filtroTipo !== '' || $filtroDependencia !== '')
                            <div class="ml-2">
                                <button wire:click="limpiarFiltros" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                                    <i class="fas fa-times mr-1"></i> Limpiar
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Tabla --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center align-middle">Tipo</th>
                                    <th class="text-center align-middle">Dependencia</th>
                                    <th class="text-center align-middle">Concepto</th>
                                    <th class="text-center align-middle">Rubro</th>
                                    <th class="text-center align-middle">S.R.</th>
                                    <th class="text-center align-middle">Fin.</th>
                                    <th class="text-center align-middle">Inc.</th>
                                    <th class="text-center align-middle">U.E.</th>
                                    <th class="text-center align-middle">%</th>
                                    <th class="text-center align-middle">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $_grupoActual = null;
                                    $_subGrupoActual = null;
                                @endphp
                                @forelse ($distribuciones as $item)
                                    @php
                                        $grupo = $item->tipo_id . '-' . $item->dependencia_id;
                                        $conceptoKey = $item->concepto ?? '_sin_concepto_';
                                        $subtotalKey = $grupo . '-' . $conceptoKey;
                                    @endphp
                                    {{-- Fila de agrupación por Tipo + Dependencia --}}
                                    @if($grupo !== $_grupoActual)
                                        @php $_grupoActual = $grupo; $_subGrupoActual = null; @endphp
                                        <tr class="table-primary">
                                            <td colspan="10" class="font-weight-bold small text-center align-middle py-2">
                                                <i class="fas fa-layer-group mr-1"></i>
                                                {{ $item->tipo?->tipo ?? '-' }}
                                                &mdash;
                                                {{ $item->dependencia?->dependencia ?? '-' }}
                                                ({{ $item->dependencia?->abreviatura ?? '-' }})
                                            </td>
                                        </tr>
                                    @endif
                                    {{-- Fila de sub-agrupación por Concepto --}}
                                    @if($conceptoKey !== $_subGrupoActual)
                                        @php $_subGrupoActual = $conceptoKey; @endphp
                                        <tr class="table-light">
                                            <td colspan="9" class="font-weight-bold small text-muted py-1 pl-4">
                                                <i class="fas fa-tag mr-1 text-secondary"></i>
                                                {{ $item->concepto ?? 'Sin concepto' }}
                                            </td>
                                            <td class="font-weight-bold small text-muted py-1 text-right text-nowrap">
                                                {{ rtrim(rtrim(number_format($subtotalesConcepto[$subtotalKey]['total'] ?? 0, 3, ',', '.'), '0'), ',') }}%
                                                ({{ $subtotalesConcepto[$subtotalKey]['cantidad'] ?? 0 }} ítem/s)
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="align-middle small">{{ $item->tipo?->tipo ?? '-' }}</td>
                                        <td class="align-middle small">{{ $item->dependencia?->abreviatura ?? '-' }}</td>
                                        <td class="align-middle small">
                                            @if($item->recurso || $item->codigo_sir)
                                                <i class="fas fa-info-circle text-info mr-1" data-toggle="tooltip" data-placement="top"
                                                    title="Recurso: {{ $item->recurso ?? '-' }} - Cod.SIR: {{ $item->codigo_sir ?? '-' }}"></i>
                                            @endif
                                            {{ $item->concepto ?? '-' }}
                                        </td>
                                        <td class="align-middle small text-right">{{ $item->rubro ?? '-' }}</td>
                                        <td class="align-middle small text-right">{{ $item->sub_rubro ?? '-' }}</td>
                                        <td class="align-middle small text-right">{{ $item->financiacion ?? '-' }}</td>
                                        <td class="align-middle small text-right">{{ $item->inciso ?? '-' }}</td>
                                        <td class="align-middle small text-right">{{ $item->unidad_ejecutora ?? '-' }}</td>
                                        <td class="align-middle small text-right">{{ rtrim(rtrim(number_format($item->porcentaje, 3, ',', '.'), '0'), ',') }}%</td>
                                        <td class="text-center align-middle">
                                            <button wire:click="showDetails({{ $item->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsDistribucionModal" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button wire:click="edit({{ $item->id }})"
                                                class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡Se eliminará la distribución SIIF!', method: 'destroy', id: {{ $item->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-3">
                                            No hay distribuciones registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $distribuciones->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Crear / Editar --}}
    <div wire:ignore.self class="modal fade" id="siifDistribucionModal" tabindex="-1" role="dialog"
        aria-labelledby="siifDistribucionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="siifDistribucionModalLabel">
                        {{ $siif_distribucion_id ? 'Editar' : 'Nueva' }} Distribución SIIF
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="tipo_id">Tipo *</label>
                                <select class="form-control @error('tipo_id') is-invalid @enderror"
                                    wire:model.defer="tipo_id" id="tipo_id" required>
                                    <option value="">Seleccionar tipo...</option>
                                    @foreach ($tipos as $t)
                                        <option value="{{ $t->id }}">{{ $t->tipo }}</option>
                                    @endforeach
                                </select>
                                @error('tipo_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="dependencia_id">Dependencia *</label>
                                <select class="form-control @error('dependencia_id') is-invalid @enderror"
                                    wire:model.defer="dependencia_id" id="dependencia_id" required>
                                    <option value="">Seleccionar dependencia...</option>
                                    @foreach ($dependencias as $d)
                                        <option value="{{ $d->id }}">{{ $d->dependencia }} ({{ $d->abreviatura }})</option>
                                    @endforeach
                                </select>
                                @error('dependencia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="concepto">Concepto</label>
                                <input type="text"
                                    class="form-control @error('concepto') is-invalid @enderror"
                                    wire:model.defer="concepto" id="concepto"
                                    placeholder="Ej: Recaudación art. 222">
                                @error('concepto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="codigo_sir">Código SIR</label>
                                <input type="text"
                                    class="form-control @error('codigo_sir') is-invalid @enderror"
                                    wire:model.defer="codigo_sir" id="codigo_sir"
                                    placeholder="Ej: 123.456">
                                @error('codigo_sir')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-2">
                                <label for="porcentaje">% *</label>
                                <input type="text"
                                    class="form-control @error('porcentaje') is-invalid @enderror"
                                    wire:model.defer="porcentaje" id="porcentaje"
                                    placeholder="0.000" required>
                                @error('porcentaje')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="rubro">Rubro</label>
                                <input type="text"
                                    class="form-control @error('rubro') is-invalid @enderror"
                                    wire:model.defer="rubro" id="rubro"
                                    placeholder="Ej: 100">
                                @error('rubro')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="sub_rubro">Subrubro</label>
                                <input type="text"
                                    class="form-control @error('sub_rubro') is-invalid @enderror"
                                    wire:model.defer="sub_rubro" id="sub_rubro"
                                    placeholder="Ej: 200">
                                @error('sub_rubro')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="recurso">Recurso</label>
                                <input type="text"
                                    class="form-control @error('recurso') is-invalid @enderror"
                                    wire:model.defer="recurso" id="recurso"
                                    placeholder="Ej: 456">
                                @error('recurso')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="financiacion">Financiación</label>
                                <input type="text"
                                    class="form-control @error('financiacion') is-invalid @enderror"
                                    wire:model.defer="financiacion" id="financiacion"
                                    placeholder="Ej: 1.1">
                                @error('financiacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="inciso">Inciso</label>
                                <input type="text"
                                    class="form-control @error('inciso') is-invalid @enderror"
                                    wire:model.defer="inciso" id="inciso"
                                    placeholder="Ej: 12">
                                @error('inciso')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="unidad_ejecutora">Unidad Ejecutora</label>
                                <input type="text"
                                    class="form-control @error('unidad_ejecutora') is-invalid @enderror"
                                    wire:model.defer="unidad_ejecutora" id="unidad_ejecutora"
                                    placeholder="Ej: 001">
                                @error('unidad_ejecutora')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button"
                        wire:click.prevent="{{ $siif_distribucion_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">
                        {{ $siif_distribucion_id ? 'Actualizar' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Detalles --}}
    <div wire:ignore.self class="modal fade" id="detailsDistribucionModal" tabindex="-1" role="dialog"
        aria-labelledby="detailsDistribucionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsDistribucionModalLabel">Detalles de Distribución SIIF</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedDistribucion)
                        <p class="mb-1"><strong>Tipo:</strong> {{ $selectedDistribucion->tipo?->tipo ?? '-' }}</p>
                        <p class="mb-1"><strong>Dependencia:</strong> {{ $selectedDistribucion->dependencia?->dependencia ?? '-' }} ({{ $selectedDistribucion->dependencia?->abreviatura ?? '-' }})</p>
                        <p class="mb-1"><strong>Concepto:</strong> {{ $selectedDistribucion->concepto ?? '-' }}</p>
                        <p class="mb-1"><strong>Rubro:</strong> {{ $selectedDistribucion->rubro ?? '-' }}</p>
                        <p class="mb-1"><strong>Subrubro:</strong> {{ $selectedDistribucion->sub_rubro ?? '-' }}</p>
                        <p class="mb-1"><strong>Recurso:</strong> {{ $selectedDistribucion->recurso ?? '-' }}</p>
                        <p class="mb-1"><strong>Código SIR:</strong> {{ $selectedDistribucion->codigo_sir ?? '-' }}</p>
                        <p class="mb-1"><strong>Porcentaje:</strong> {{ rtrim(rtrim(number_format($selectedDistribucion->porcentaje, 3, ',', '.'), '0'), ',') }}%</p>
                        <p class="mb-1"><strong>Financiación:</strong> {{ $selectedDistribucion->financiacion ?? '-' }}</p>
                        <p class="mb-1"><strong>Inciso:</strong> {{ $selectedDistribucion->inciso ?? '-' }}</p>
                        <p class="mb-1"><strong>Unidad Ejecutora:</strong> {{ $selectedDistribucion->unidad_ejecutora ?? '-' }}</p>
                        <hr>
                        <p class="mb-1 text-muted small"><strong>Creado:</strong> {{ $selectedDistribucion->created_at?->format('d/m/Y H:i') }}</p>
                        <p class="mb-0 text-muted small"><strong>Última actualización:</strong> {{ $selectedDistribucion->updated_at?->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        wire:click="resetDetails()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.addEventListener('show-modal', event => {
                if (event.detail.id === 'siifDistribucionModal') {
                    $('#siifDistribucionModal').modal('show');
                }
            });

            window.livewire.on('siifDistribucionStore', () => {
                $('#siifDistribucionModal').modal('hide');
            });

            window.livewire.on('siifDistribucionUpdate', () => {
                $('#siifDistribucionModal').modal('hide');
            });

            $(document).ready(function() {
                $('#siifDistribucionModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });
                initTooltips();
            });

            document.addEventListener('livewire:load', function() {
                window.livewire.hook('message.processed', function() {
                    setTimeout(initTooltips, 50);
                });
            });

            function initTooltips() {
                $('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
            }
        </script>
    @endpush
</div>
