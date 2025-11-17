<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">
                <i class="fas fa-handshake mr-2"></i>Entregas de Libretas de Valores
            </h4>
            <div>
                <a href="{{ route('tesoreria.valores.index') }}" class="btn btn-success mr-2">
                    <i class="fas fa-barcode mr-1"></i> Libretas de Valores
                </a>
                <button wire:click="create()" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Registrar Entrega
                </button>
            </div>
        </div>
        <div class="card-body px-2 py-2">
            <!-- Filtros -->
            <div class="d-flex flex-justify-content-between mb-1">
                <div>
                    <div class="input-group">
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por recibo, serie o número...">
                    </div>
                </div>
                <div>
                    <input type="date" wire:model="fecha_desde" class="form-control" placeholder="Fecha desde">
                </div>
                <div>
                    <input type="date" wire:model="fecha_hasta" class="form-control" placeholder="Fecha hasta">
                </div>
                <div>
                    <select wire:model="servicio_filtro" class="form-control">
                        <option value="">Todos los servicios</option>
                        @foreach($servicios as $servicio)
                            <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button class="btn btn-outline-danger btn-block" title="Limpiar filtros" wire:click="$set('search', ''); $set('fecha_desde', ''); $set('fecha_hasta', ''); $set('servicio_filtro', '')" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Recibo Entrega</th>
                            <th>Tipo Libreta</th>
                            <th>Numeración</th>
                            <th>Servicio</th>
                            <th class="text-center">Fecha Entrega</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entregas as $entrega)
                            <tr>
                                <td>{{ $entrega->numero_recibo_entrega }}</td>
                                <td>{{ $entrega->libretaValor->tipoLibreta->nombre }}</td>
                                <td>{{ $entrega->libretaValor->numero_inicial }} al {{ $entrega->libretaValor->numero_final }}</td>
                                <td>{{ $entrega->servicio->nombre }}</td>
                                <td class="text-center">{{ $entrega->fecha_entrega->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <button wire:click="confirmAnular({{ $entrega->id }})" class="btn btn-sm btn-danger" title="Anular Entrega">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron entregas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{ $entregas->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Registrar Entrega -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Entrega de Libreta</h5>
                    <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="libreta_valor_id">Libreta a Entregar</label>
                        <select wire:model.live="libreta_valor_id" id="libreta_valor_id" class="form-control @error('libreta_valor_id') is-invalid @enderror">
                            <option value="">Seleccione una libreta...</option>
                            @foreach($libretasDisponibles as $libreta)
                                <option value="{{ $libreta->id }}">
                                    {{ $libreta->tipoLibreta->nombre }} - N° {{ $libreta->numero_inicial }} al {{ $libreta->numero_final }}
                                    @if($libreta->serie) (Serie: {{ $libreta->serie }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('libreta_valor_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="servicio_id">Servicio de Asignación</label>
                        <select wire:model.defer="servicio_id" id="servicio_id" class="form-control @error('servicio_id') is-invalid @enderror">
                            <option value="">Seleccione un servicio...</option>
                            @foreach($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                        @error('servicio_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_recibo_entrega">N° Recibo de Entrega</label>
                                <input type="text" wire:model.defer="numero_recibo_entrega" id="numero_recibo_entrega" class="form-control @error('numero_recibo_entrega') is-invalid @enderror" placeholder="Ingrese el número de recibo">
                                @error('numero_recibo_entrega') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_entrega">Fecha de Entrega</label>
                                <input type="date" wire:model.defer="fecha_entrega" id="fecha_entrega" class="form-control @error('fecha_entrega') is-invalid @enderror">
                                @error('fecha_entrega') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="observaciones">Observaciones (opcional)</label>
                        <textarea wire:model.defer="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3" placeholder="Observaciones adicionales..."></textarea>
                        @error('observaciones') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save()">Registrar Entrega</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Anular Entrega -->
    @if($showAnularModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Anulación</h5>
                    <button type="button" class="close" wire:click="closeAnularModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea anular esta entrega? La libreta volverá a estar disponible en stock.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeAnularModal">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click="anular()">Anular Entrega</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

