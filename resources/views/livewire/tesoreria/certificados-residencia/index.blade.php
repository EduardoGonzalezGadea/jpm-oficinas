<div class="container-fluid px-0">
    @section('title', 'Certificados de Residencia')

    <div class="card">
        <div class="card-header bg-dark text-white">
            <h3 class="card-title">Gestión de Certificados de Residencia</h3>
        </div>
        <div class="card-body px-2">
            <div class="row mb-3 d-print-none">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" wire:model.debounce.300ms="search" class="form-control" placeholder="Buscar por titular o documento...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model="year" class="form-control">
                        @foreach($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="Recibido">Recibido</option>
                        <option value="Entregado">Entregado</option>
                        <option value="Devuelto">Devuelto</option>
                    </select>
                </div>
                <div class="col-md-2 pt-2">
                    <small class="text-muted">{{ $totalRegistros }} registros</small>
                </div>
                <div class="col-md-2 text-right">
                    <button class="btn btn-success" wire:click="$emit('showCreateModal')">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-dark align-middle">
                        <tr>
                            <th class="align-middle">Fecha Recibido</th>
                            <th class="align-middle">Titular</th>
                            <th class="align-middle">Documento</th>
                            <th class="align-middle">Estado</th>
                            <th class="align-middle">Recibido por</th>
                            <th class="align-middle">Entr./Dev.</th>
                            <th class="align-middle d-print-none">Acciones</th></th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        @forelse($certificados as $certificado)
                            <tr>
                                <td class="align-middle">{{ \Carbon\Carbon::parse($certificado->fecha_recibido)->format('d/m/Y') }}</td>
                                <td class="align-middle">{{ $certificado->titular_nombre }} {{ $certificado->titular_apellido }}</td>
                                <td class="align-middle">{{ $certificado->titular_tipo_documento }}: {{ $certificado->titular_nro_documento }}</td>
                                <td class="align-middle">
                                    @if($certificado->estado == 'Recibido')
                                        <span class="badge badge-primary">{{ $certificado->estado }}</span>
                                    @elseif($certificado->estado == 'Entregado')
                                        <span class="badge badge-success">{{ $certificado->estado }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $certificado->estado }}</span>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $certificado->receptor->nombre }} {{ $certificado->receptor->apellido }}</td>
                                <td class="align-middle">
                                    @if($certificado->fecha_entregado)
                                        {{ \Carbon\Carbon::parse($certificado->fecha_entregado)->format('d/m/Y') }}
                                    @elseif($certificado->fecha_devuelto)
                                        {{ \Carbon\Carbon::parse($certificado->fecha_devuelto)->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="align-middle text-center d-print-none">
                                    <div class="btn-group" role="group" aria-label="Acciones">
                                        @if($certificado->estado == 'Recibido')
                                            <button class="btn btn-sm btn-info" title="Entregar" wire:click="$emit('showDeliverModal', {{ $certificado->id }})">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" title="Devolver" wire:click="$emit('showReturnModal', {{ $certificado->id }})">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-secondary" title="Ver Detalles" wire:click="$emit('showDetailModal', {{ $certificado->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" title="Editar" wire:click="$emit('showEditModal', {{ $certificado->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" title="Eliminar" wire:click="confirmDelete({{ $certificado->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay certificados para el año seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-center d-print-none">
                {{ $certificados->links() }}
            </div>
        </div>
    </div>

    @livewire('tesoreria.certificados-residencia.create')
    @livewire('tesoreria.certificados-residencia.update')
    @livewire('tesoreria.certificados-residencia.show')
    @livewire('tesoreria.certificados-residencia.edit')
</div>
