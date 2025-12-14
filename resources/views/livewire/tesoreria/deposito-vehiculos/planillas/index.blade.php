<div>
    <div class="card">
        <div class="card-header bg-success text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-list-alt mr-2"></i>Planillas de Depósito de Vehículos</strong></h4>
            <div>
                <a href="{{ route('tesoreria.deposito-vehiculos.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Volver a Depósitos
                </a>
            </div>
        </div>
        <div class="card-body px-2 pt-1">
            <div class="row mb-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por número...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th class="align-middle">Número</th>
                            <th class="align-middle">Fecha</th>
                            <th class="align-middle text-center">Cantidad Depósitos</th>
                            <th class="align-middle">Estado</th>
                            <th class="align-middle text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($planillas as $planilla)
                            <tr>
                                <td class="align-middle">{{ $planilla->numero }}</td>
                                <td class="align-middle">{{ $planilla->fecha->format('d/m/Y') }}</td>
                                <td class="align-middle text-center">{{ $planilla->depositos_count }}</td>
                                <td class="align-middle">
                                    @if($planilla->isAnulada())
                                        <span class="badge badge-danger">
                                            Anulada - {{ $planilla->anulada_fecha->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="badge badge-success">Activa</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('tesoreria.deposito-vehiculos.planillas.show', $planilla->id) }}" class="btn btn-sm btn-info" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('tesoreria.deposito-vehiculos.planillas.print', $planilla->id) }}" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    @if(!$planilla->isAnulada())
                                        <button wire:click="confirmAnular({{ $planilla->id }})" class="btn btn-sm btn-warning" title="Anular">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No hay planillas registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $planillas->links() }}
            </div>
        </div>
    </div>
</div>
