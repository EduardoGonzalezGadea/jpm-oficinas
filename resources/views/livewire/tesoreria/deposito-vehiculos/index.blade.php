<div>
    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-car mr-2"></i>Gestión de Depósito de Vehículos</strong></h4>
            <div>
                <a href="{{ route('tesoreria.deposito-vehiculos.planillas.index') }}" class="btn btn-success mr-2">
                    <i class="fas fa-list-alt"></i> Planillas
                </a>
                <button wire:click="createPlanilla" class="btn btn-warning mr-2" @if(count($selectedDepositos) == 0) disabled @endif>
                    <i class="fas fa-plus-circle"></i> Crear Planilla @if(count($selectedDepositos) > 0) ({{ count($selectedDepositos) }}) @endif
                </button>
                <button wire:click="$emit('showCreateModal')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Registro
                </button>
            </div>
        </div>
        <div class="card-body px-2 pt-1">
            <div class="row mb-1 d-print-none">
                <div class="col-12">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por titular, cédula, recibo...">
                        <div class="input-group-append">
                            <select wire:model="selectedYear" class="custom-select" style="border-radius: 0;">
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                            <button wire:click="clearSearch" class="btn btn-outline-danger" title="Limpiar filtro">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th class="align-middle text-center" style="width: 50px;">
                                <input type="checkbox" wire:model="selectAll" style="transform: scale(1.2); cursor: pointer;">
                            </th>
                            <th class="align-middle">Fecha</th>
                            <th class="align-middle">Serie/N°</th>
                            <th class="align-middle">Titular</th>
                            <th class="align-middle">Cédula</th>
                            <th class="align-middle">Monto</th>
                            <th class="align-middle">Medio Pago</th>
                            <th class="align-middle d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($depositos as $deposito)
                            <tr>
                                <td class="align-middle text-center">
                                    @if(is_null($deposito->planilla_id))
                                        <input type="checkbox" wire:model="selectedDepositos" value="{{ $deposito->id }}" style="transform: scale(1.2); cursor: pointer;">
                                    @else
                                        <i class="fas fa-check text-success" title="En planilla"></i>
                                    @endif
                                </td>
                                <td class="align-middle">{{ \Carbon\Carbon::parse($deposito->recibo_fecha)->format('d/m/Y') }}</td>
                                <td class="align-middle">{{ $deposito->recibo_serie }} {{ $deposito->recibo_numero }}</td>
                                <td class="align-middle">{{ $deposito->titular }}</td>
                                <td class="align-middle">{{ $deposito->cedula }}</td>
                                <td class="align-middle text-right text-nowrap">${{ number_format($deposito->monto, 2, ',', '.') }}</td>
                                <td class="align-middle">{{ $deposito->medioPago->nombre ?? '-' }}</td>
                                <td class="align-middle text-center d-print-none">
                                    <button wire:click="$emit('showDetailModal', {{ $deposito->id }})" class="btn btn-sm btn-primary" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button wire:click="$emit('showEditModal', {{ $deposito->id }})" class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $deposito->id }})" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No hay registros disponibles</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                {{ $depositos->links() }}
            </div>
        </div>
    </div>

    <livewire:tesoreria.deposito-vehiculos.create />
    <livewire:tesoreria.deposito-vehiculos.edit />
    <livewire:tesoreria.deposito-vehiculos.show />
</div>
