<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>Cuentas Bancarias</h5>
            <button wire:click="create" type="button" class="btn btn-primary btn-sm">+ Nuevo</button>
        </div>
        <div class="card-body px-2">
            <input type="text" wire:model.debounce.500ms="search" class="form-control mb-3" placeholder="Buscar...">
            @if($cuentas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr class="text-center">
                                <th class="align-middle">Banco</th>
                                <th class="align-middle">Número de Cuenta</th>
                                <th class="align-middle">Tipo</th>
                                <th class="align-middle">Estado</th>
                                <th class="align-middle">Observaciones</th>
                                <th class="align-middle">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuentas as $cuenta)
                                <tr class="text-center">
                                    <td class="align-middle">{{ $cuenta->banco->codigo }}</td>
                                    <td class="align-middle">{{ $cuenta->numero_cuenta }}</td>
                                    <td class="align-middle">{{ $cuenta->tipo }}</td>
                                    <td class="align-middle">
                                        @if($cuenta->activa)
                                            <span class="badge badge-success">Activa</span>
                                        @else
                                            <span class="badge badge-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $cuenta->observaciones }}</td>
                                    <td class="align-middle">
                                        <div class="d-flex justify-content-center">
                                            <button wire:click="edit({{ $cuenta->id }})" class="btn btn-sm btn-primary mr-1" title="Editar"><i class="fas fa-edit"></i></button>
                                            <button wire:click="deleteConfirm({{ $cuenta->id }})" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $cuentas->links() }}
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay cuentas bancarias registradas aún. Haz clic en "Nuevo" para agregar la primera cuenta.
                </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">
                        @if($showCreate)
                            Nueva Cuenta Bancaria
                        @elseif($showEdit)
                            Editar Cuenta Bancaria
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($showCreate)
                        <livewire:tesoreria.cuenta-bancaria.cuenta-create />
                    @elseif($showEdit)
                        <livewire:tesoreria.cuenta-bancaria.cuenta-edit :cuentaId="$cuentaId" />
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.addEventListener('show-modal', event => {
                const modalId = event.detail.id;
                $(`#${modalId}`).modal('show');
            });

            window.addEventListener('close-modal', event => {
                $('#modal').modal('hide');
            });

            window.livewire.on('cuentaStore', () => {
                $('#modal').modal('hide');
            });

            window.livewire.on('cuentaUpdate', () => {
                $('#modal').modal('hide');
            });

            $(document).ready(function() {
                $('#modal').on('hidden.bs.modal', function() {
                    window.livewire.emit('closeModal');
                });
            });
        </script>
    @endpush
</div>
