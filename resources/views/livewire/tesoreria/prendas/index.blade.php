<div>
    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-file-invoice-dollar mr-2"></i>Gestión de Prendas</strong></h4>
            <div>
                <a href="{{ route('tesoreria.prendas.reportes') }}" class="btn btn-secondary">
                    <i class="fas fa-filter"></i> Filtrar
                </a>
                <a href="{{ route('tesoreria.prendas.planillas.index') }}" class="btn btn-success">
                    <i class="fas fa-list-alt"></i> Planillas
                </a>
                <button wire:click="createPlanilla" class="btn btn-primary" style="background-color: #6f42c1; border-color: #6f42c1; color: white;" @if(count($selectedPrendas)==0) disabled @endif>
                    <i class="fas fa-plus-circle"></i> Crear Planilla @if(count($selectedPrendas) > 0) ({{ count($selectedPrendas) }}) @endif
                </button>
                <a href="{{ route('tesoreria.prendas.cargar-cfe') }}" class="btn btn-warning">
                    <i class="fas fa-file-upload"></i> Cargar CFE
                </a>
                <button wire:click="$emit('showCreateModal')" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo
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
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por titular, cédula, recibo, transferencia...">
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
                            <th class="align-middle">Recibo</th>
                            <th class="align-middle">Orden Cobro</th>
                            <th class="align-middle">Titular</th>
                            <th class="align-middle">Cédula</th>
                            <th class="align-middle">Monto</th>
                            <th class="align-middle">Medio Pago</th>
                            <th class="align-middle d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($prendas as $prenda)
                        <tr>
                            <td class="align-middle text-center">
                                @if(is_null($prenda->planilla_id))
                                <input type="checkbox" wire:model="selectedPrendas" value="{{ $prenda->id }}" style="transform: scale(1.2); cursor: pointer;">
                                @else
                                <i class="fas fa-check text-success" title="En planilla"></i>
                                @endif
                            </td>
                            <td class="align-middle">{{ \Carbon\Carbon::parse($prenda->recibo_fecha)->format('d/m/Y') }}</td>
                            <td class="align-middle">{{ $prenda->recibo_serie }} {{ $prenda->recibo_numero }}</td>
                            <td class="align-middle">{{ $prenda->orden_cobro }}</td>
                            <td class="align-middle">{{ $prenda->titular_nombre }}</td>
                            <td class="align-middle">{{ $prenda->titular_cedula }}</td>
                            <td class="align-middle text-right">${{ $prenda->monto_formateado }}</td>
                            <td class="align-middle">{{ $prenda->medioPago->nombre ?? '-' }}</td>
                            <td class="align-middle text-center py-1 px-2 d-print-none" style="white-space: nowrap;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button wire:click="$emit('showDetailModal', {{ $prenda->id }})" class="btn btn-primary" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button wire:click="$emit('showEditModal', {{ $prenda->id }})" class="btn btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="confirmDelete({{ $prenda->id }})" class="btn btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">No hay registros disponibles</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">
                {{ $prendas->links() }}
            </div>
        </div>
    </div>

    <livewire:tesoreria.prendas.create />
    <livewire:tesoreria.prendas.edit />
    <livewire:tesoreria.prendas.show />

    @if($autoEditPrendaId)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.livewire.emit('showEditModal', @json($autoEditPrendaId));
            }, 500);
        });
    </script>
    @endif
</div>