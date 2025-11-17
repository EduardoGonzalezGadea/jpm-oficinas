<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">
                <i class="fas fa-chart-bar mr-2"></i>Reportes de Libretas de Valores
            </h4>
        </div>
        <div class="card-body">
            <!-- Navegación de pestañas para tipos de reporte -->
            <ul class="nav nav-tabs" id="reportesTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $reporteTipo === 'completas' ? 'active' : '' }}"
                       wire:click="cambiarReporte('completas')" role="tab">
                        <i class="fas fa-box mr-1"></i>Libretas Completas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $reporteTipo === 'en_uso' ? 'active' : '' }}"
                       wire:click="cambiarReporte('en_uso')" role="tab">
                        <i class="fas fa-handshake mr-1"></i>Libretas en Uso
                    </a>
                </li>
            </ul>

            <div class="tab-content mt-3">
                <!-- Controles de filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select wire:model.live="filtroTipoLibreta" class="form-control">
                            <option value="">Todos los tipos</option>
                            @foreach($tiposLibreta as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($reporteTipo === 'en_uso')
                    <div class="col-md-3">
                        <select wire:model.live="filtroServicio" class="form-control">
                            <option value="">Todos los servicios</option>
                            @foreach($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar...">
                    </div>
                    <div class="col-md-3">
                        <button wire:click="limpiarFiltros" class="btn btn-secondary">
                            <i class="fas fa-eraser mr-1"></i>Limpiar Filtros
                        </button>
                    </div>
                </div>

                <!-- Contenido del reporte -->
                @if($reporteTipo === 'completas')
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Serie</th>
                                    <th class="text-center">Numeración</th>
                                    <th class="text-center">Próximo Recibo</th>
                                    <th class="text-center">Fecha Recepción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($libretas as $libreta)
                                    <tr>
                                        <td>{{ $libreta->tipoLibreta->nombre }}</td>
                                        <td>{{ $libreta->serie ?? '-' }}</td>
                                        <td class="text-center">{{ $libreta->numero_inicial }} al {{ $libreta->numero_final }}</td>
                                        <td class="text-center">{{ $libreta->proximo_recibo_disponible }}</td>
                                        <td class="text-center">{{ $libreta->fecha_recepcion->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay libretas completas disponibles.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $libretas->links() }}
                    </div>
                @elseif($reporteTipo === 'en_uso')
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Servicio</th>
                                    <th>Tipo Libreta</th>
                                    <th>Serie</th>
                                    <th class="text-center">Numeración</th>
                                    <th class="text-center">Próximo Recibo</th>
                                    <th class="text-center">Fecha Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entregas as $entrega)
                                    <tr>
                                        <td>{{ $entrega->servicio->nombre }}</td>
                                        <td>{{ $entrega->libretaValor->tipoLibreta->nombre }}</td>
                                        <td>{{ $entrega->libretaValor->serie ?? '-' }}</td>
                                        <td class="text-center">{{ $entrega->libretaValor->numero_inicial }} al {{ $entrega->libretaValor->numero_final }}</td>
                                        <td class="text-center">{{ $entrega->libretaValor->proximo_recibo_disponible }}</td>
                                        <td class="text-center">{{ $entrega->fecha_entrega->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No hay libretas en uso.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $entregas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
