<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">
                <i class="fas fa-barcode mr-2"></i>Libretas de Valores
            </h4>
            <div>
                <a href="{{ route('tesoreria.valores.entregas') }}" class="btn btn-success mr-2">
                    <i class="fas fa-handshake mr-1"></i> Entregas
                </a>
                <a href="{{ route('tesoreria.valores.servicios') }}" class="btn btn-success mr-2">
                    <i class="fas fa-cogs mr-1"></i> Servicios
                </a>
                <a href="{{ route('tesoreria.valores.tipos-libreta') }}" class="btn btn-success mr-2">
                    <i class="fas fa-book mr-1"></i> Tipos
                </a>
                <a href="{{ route('tesoreria.valores.reportes') }}" class="btn btn-info mr-2">
                    <i class="fas fa-chart-bar mr-1"></i> Reportes
                </a>
                <button wire:click="create()" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Ingreso de Libreta
                </button>
            </div>
        </div>
        <div class="card-body px-2 py-2">
            <div class="d-flex flex-justify-content-between mb-1">
                <div class="flex-fill">
                    <div class="input-group">
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por tipo, serie o número...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
                <div class="flex-fill">
                    <select wire:model="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="en_stock">En Stock</option>
                        <option value="asignada">Asignada</option>
                        <option value="agotada">Agotada</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Tipo</th>
                            <th>Serie</th>
                            <th class="text-center">Numeración</th>
                            <th class="text-center">Próximo Recibo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Fecha Recepción</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($libretas as $libreta)
                            <tr>
                                <td>{{ $libreta->tipoLibreta->nombre }}</td>
                                <td>{{ $libreta->serie ?? '-' }}</td>
                                <td class="text-center">{{ $libreta->numero_inicial }} al {{ $libreta->numero_final }}</td>
                                <td class="text-center">{{ $libreta->proximo_recibo_disponible }}</td>
                                <td class="text-center"><span class="badge badge-primary">{{ $libreta->estado }}</span></td>
                                <td class="text-center">{{ $libreta->fecha_recepcion->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    @if($libreta->estado === 'en_stock')
                                    <button class="btn btn-sm btn-info mr-1" title="Entregar Libreta" wire:click="entregarLibreta({{ $libreta->id }})">
                                        <i class="fas fa-handshake"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-sm btn-danger" title="Eliminar" wire:click="confirmDelete({{ $libreta->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay libretas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                {{ $libretas->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Ingreso -->
    @if($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ingreso de Libreta</h5>
                    <button type="button" class="close" wire:click="closeModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tipo_libreta_id">Tipo de Libreta</label>
                        <select wire:model.live="tipo_libreta_id" id="tipo_libreta_id" class="form-control @error('tipo_libreta_id') is-invalid @enderror">
                            <option value="">Seleccione un tipo...</option>
                            @foreach($tiposLibreta as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }} ({{ $tipo->cantidad_recibos }} recibos)</option>
                            @endforeach
                        </select>
                        @error('tipo_libreta_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="serie">Serie (opcional)</label>
                                <input type="text" wire:model.defer="serie" id="serie" class="form-control @error('serie') is-invalid @enderror">
                                @error('serie') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_recepcion">Fecha de Recepción</label>
                                <input type="date" wire:model.defer="fecha_recepcion" id="fecha_recepcion" class="form-control @error('fecha_recepcion') is-invalid @enderror">
                                @error('fecha_recepcion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="numero_inicial">N° Recibo Inicial</label>
                                <input type="number" wire:model.live="numero_inicial" id="numero_inicial" class="form-control @error('numero_inicial') is-invalid @enderror">
                                @error('numero_inicial') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cantidad_libretas">Cantidad de Libretas</label>
                                <input type="number" wire:model.live="cantidad_libretas" id="cantidad_libretas" class="form-control @error('cantidad_libretas') is-invalid @enderror">
                                @error('cantidad_libretas') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    @if($numero_final_calculado)
                    <div class="alert alert-info">
                        El lote de libretas finalizará en el recibo N° <strong>{{ $numero_final_calculado }}</strong>.
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save()">Registrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal Entregar Libreta -->
    @if($showEntregaModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Entregar Libreta de Valores</h5>
                    <button type="button" class="close" wire:click="closeEntregaModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($libretaSeleccionada)
                    <div class="alert alert-info">
                        <strong>Libreta seleccionada:</strong> {{ $libretaSeleccionada->tipoLibreta->nombre }} - N° {{ $libretaSeleccionada->numero_inicial }} al {{ $libretaSeleccionada->numero_final }}
                        @if($libretaSeleccionada->serie) (Serie: {{ $libretaSeleccionada->serie }}) @endif
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="servicio_entrega_id">Servicio de Asignación</label>
                        <select wire:model.defer="servicio_entrega_id" id="servicio_entrega_id" class="form-control @error('servicio_entrega_id') is-invalid @enderror">
                            <option value="">Seleccione un servicio...</option>
                            @foreach($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                        @error('servicio_entrega_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
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
                        <label for="observaciones_entrega">Observaciones (opcional)</label>
                        <textarea wire:model.defer="observaciones_entrega" id="observaciones_entrega" class="form-control @error('observaciones_entrega') is-invalid @enderror" rows="3" placeholder="Observaciones adicionales..."></textarea>
                        @error('observaciones_entrega') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeEntregaModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="registrarEntrega()">Registrar Entrega</button>
                </div>
            </div>
        </div>
    </div>

    @if($showEntregaModal)
        <div class="modal-backdrop fade show"></div>
    @endif
    @endif

    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            // Listener for modal opened event
            Livewire.on('modalOpened', () => {
                setTimeout(() => {
                    const modalBody = document.querySelector('.modal.show .modal-body');
                    if (modalBody) {
                        modalBody.scrollTop = 0;
                    }
                    const campoId = '{{ $campoEnfoque }}';
                    const campo = document.getElementById(campoId);
                    if (campo) {
                        campo.focus();
                        campo.select();
                    }
                }, 200); // Increased delay to ensure modal is fully rendered
            });
        });
    </script>
    @endpush
</div>
