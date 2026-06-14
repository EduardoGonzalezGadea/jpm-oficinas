<div class="container-fluid px-0">
    @section('title', 'Tarjetas de Cobro BROU')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0"><strong><i class="fas fa-credit-card mr-2"></i>Gestión de Tarjetas de Cobro BROU</strong></h4>
                <div>
                    <a href="{{ route('tesoreria.tarjetas-cobro-brou.reportes') }}" class="btn btn-secondary mr-2">
                        <i class="fas fa-filter"></i> Reportes
                    </a>
                    <button class="btn btn-primary" wire:click="$emit('showCreateModal')">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body px-2 pt-1">
            <div class="d-flex mb-1 d-print-none align-items-center">
                <div class="flex-grow-1 mr-1" style="max-width: 40%;">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por titular, cédula o tarjeta...">
                    </div>
                </div>

                <div class="flex-grow-1 {{ $estado !== 'Recibido' ? 'mr-1' : '' }}">
                    <div class="input-group">
                        <select wire:model="estado" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="Recibido">Recibido</option>
                            <option value="Entregado">Entregado</option>
                            <option value="Devuelto">Devuelto</option>
                        </select>
                        @if($estado === 'Recibido')
                        <div class="input-group-append">
                            <button wire:click="clearFilters" class="btn btn-outline-danger" title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                @if($estado !== 'Recibido')
                <div class="input-group mr-1" style="width: 140px;">
                    <select wire:model="year" class="form-control">
                        @foreach($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    <div class="input-group-append">
                        <button wire:click="clearFilters" class="btn btn-outline-danger" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                @endif

                <div class="text-nowrap ml-1">
                    <small>{{ $totalRegistros }} registros</small>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th class="align-middle">Recibido</th>
                            <th class="align-middle">Cédula</th>
                            <th class="align-middle">Titular</th>
                            <th class="align-middle">Nro. Tarjeta</th>
                            <th class="align-middle">Estado</th>
                            <th class="align-middle">Entr./Dev.</th>
                            <th class="align-middle">Observaciones</th>
                            <th class="align-middle d-print-none">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($tarjetas as $tarjeta)
                        <tr>
                            <td class="align-middle">{{ \Carbon\Carbon::parse($tarjeta->fecha_recibido)->format('d/m/Y') }}</td>
                            <td class="align-middle">{{ $tarjeta->titular_cedula }}</td>
                            <td class="align-middle">{{ $tarjeta->titular_nombre }} {{ $tarjeta->titular_apellido }}</td>
                            <td class="align-middle">{{ $tarjeta->numero_tarjeta }}</td>
                            <td class="align-middle">
                                @if($tarjeta->estado == 'Recibido')
                                <span class="badge badge-primary">{{ $tarjeta->estado }}</span>
                                @elseif($tarjeta->estado == 'Entregado')
                                <span class="badge badge-success">{{ $tarjeta->estado }}</span>
                                @else
                                <span class="badge badge-danger">{{ $tarjeta->estado }}</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($tarjeta->fecha_entregado)
                                {{ \Carbon\Carbon::parse($tarjeta->fecha_entregado)->format('d/m/Y') }}
                                @elseif($tarjeta->fecha_devuelto)
                                {{ \Carbon\Carbon::parse($tarjeta->fecha_devuelto)->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="align-middle">{{ Str::limit($tarjeta->observaciones, 50) }}</td>
                            <td class="align-middle text-center d-print-none">
                                <div class="btn-group" role="group" aria-label="Acciones">
                                    @if($tarjeta->estado == 'Recibido')
                                    <button class="btn btn-sm btn-info" title="Entregar" wire:click="$emit('showDeliverModal', {{ $tarjeta->id }})">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" title="Devolver" wire:click="$emit('showReturnModal', {{ $tarjeta->id }})">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                    <button class="btn btn-sm btn-secondary" title="Ver Detalles" wire:click="$emit('showDetailModal', {{ $tarjeta->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary" title="Editar" wire:click="$emit('showEditModal', {{ $tarjeta->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" title="Eliminar" wire:click="confirmDelete({{ $tarjeta->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay tarjetas registradas.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center d-print-none">
                {{ $tarjetas->links() }}
            </div>
        </div>
    </div>

    @livewire('tesoreria.tarjetas-cobro-brou.create')
    @livewire('tesoreria.tarjetas-cobro-brou.update')
    @livewire('tesoreria.tarjetas-cobro-brou.show')
    @livewire('tesoreria.tarjetas-cobro-brou.edit')
</div>