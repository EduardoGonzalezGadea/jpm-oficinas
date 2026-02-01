<div>
    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-list mr-2"></i>Planillas de Porte de Armas</strong></h4>
            <a href="{{ route('tesoreria.armas.porte') }}" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        <div class="card-body p-2">
            @if (session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif

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
                    <thead class="thead-dark">
                        <tr>
                            <th>Número</th>
                            <th>Fecha</th>
                            <th class="text-center">Registros</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($planillas as $planilla)
                        <tr>
                            <td class="align-middle">{{ $planilla->numero }}</td>
                            <td class="align-middle">{{ $planilla->fecha->format('d/m/Y') }}</td>
                            <td class="align-middle text-center">{{ $planilla->porte_armas_count }}</td>
                            <td class="align-middle">
                                @if($planilla->isAnulada())
                                <span class="badge badge-danger">Anulada</span>
                                @else
                                <span class="badge badge-success">Activa</span>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                <a href="{{ route('tesoreria.armas.porte.planillas.show', $planilla->id) }}" class="btn btn-sm btn-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('tesoreria.armas.porte.planillas.imprimir', $planilla->id) }}" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">
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

            <div class="mt-2">
                {{ $planillas->links() }}
            </div>
        </div>
    </div>
</div>