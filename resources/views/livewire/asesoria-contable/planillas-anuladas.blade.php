<div class="container-fluid px-0">
    @section('title', 'Planillas Anuladas')

    <div class="card">
        <div class="card-header bg-danger text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-ban mr-2"></i>Planillas Anuladas</strong>
                </h4>
                <a href="{{ route('asesoria-contable.estados-recaudacion') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left mr-1"></i>Volver a Planillas para Estados de Recaudación
                </a>
            </div>
        </div>

        <div class="card-body px-2 pt-1">
            <div class="d-flex mb-2 align-items-center">
                <div class="flex-grow-1 mr-2" style="min-width: 200px;">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                            placeholder="Buscar por planilla o N° ER...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-dark" wire:click="resetearBusqueda" title="Resetear búsqueda">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="dropdown mr-2" style="width: 200px;" id="dropdownMesesWrapper" wire:ignore.self>
                    <button class="btn btn-white border form-control dropdown-toggle text-left d-flex justify-content-between align-items-center" type="button" id="dropdownMeses" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="text-truncate">
                            @if(empty($filtroMeses))
                                — Todos los meses —
                            @else
                                {{ count($filtroMeses) }} {{ count($filtroMeses) === 1 ? 'mes' : 'meses' }}
                            @endif
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right p-3" aria-labelledby="dropdownMeses" style="min-width: 240px; max-height: 350px; overflow-y: auto;" onclick="event.stopPropagation()" wire:ignore.self>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <span class="font-weight-bold small text-secondary">Meses del año</span>
                            <a href="#" wire:click.prevent="limpiarFiltroMeses" class="small font-weight-bold text-danger">
                                Limpiar
                            </a>
                        </div>
                        @php
                            $mesesNombres = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                        @endphp
                        @foreach($mesesNombres as $num => $nombre)
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" id="mes_{{ $num }}" value="{{ $num }}" wire:model.live="filtroMeses" class="custom-control-input">
                                <label for="mes_{{ $num }}" class="custom-control-label small cursor-pointer w-100">{{ $nombre }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mr-2" style="width: 170px;">
                    <select wire:model.live="filtroAno" class="form-control">
                        <option value="0">— Todos los años —</option>
                        @foreach($anosRegistrados as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="align-middle">
                        <tr>
                            <th>Fecha</th>
                            <th>N°</th>
                            <th>Tipo</th>
                            <th>Dependencia</th>
                            <th>Turno</th>
                            <th>Motivo de Anulación</th>
                            <th>Fecha Anulación</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($planillas as $p)
                            <tr>
                                <td class="align-middle">{{ $p->fecha ? $p->fecha->format('d/m/Y') : '—' }}</td>
                                <td class="align-middle">{{ $p->numero }}</td>
                                <td class="align-middle">{{ $p->tipo->tipo ?? '—' }}</td>
                                <td class="align-middle">{{ $p->dependencia->dependencia ?? '—' }}</td>
                                <td class="align-middle">{{ $p->turno ?? '—' }}</td>
                                <td class="align-middle">
                                    <span class="text-danger font-weight-bold small">{{ $p->motivo_anulacion ?: '—' }}</span>
                                </td>
                                <td class="align-middle">{{ $p->deleted_at ? $p->deleted_at->format('d/m/Y H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> No hay planillas anuladas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted">
                    Mostrando {{ $planillas->firstItem() ?? 0 }} a {{ $planillas->lastItem() ?? 0 }}
                    de {{ $planillas->total() }} resultados
                </small>
                {{ $planillas->links() }}
            </div>
        </div>
    </div>
</div>
