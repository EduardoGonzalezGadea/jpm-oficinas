<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>Bancos</h5>
            <button wire:click="create" type="button" class="btn btn-primary btn-sm" onclick="console.log('Button clicked from onclick')">+ Nuevo</button>
        </div>
        <div class="card-body px-2">
            <input type="text" wire:model.debounce.500ms="search" class="form-control mb-3" placeholder="Buscar...">
            @if($bancos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bancos as $banco)
                                <tr>
                                    <td>{{ $banco->nombre }}</td>
                                    <td>{{ $banco->codigo }}</td>
                                    <td>{{ $banco->observaciones }}</td>
                                    <td>
                                        <button wire:click="edit({{ $banco->id }})" class="btn btn-sm btn-primary">Editar</button>
                                        <button wire:click="deleteConfirm({{ $banco->id }})" class="btn btn-sm btn-danger">Eliminar</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $bancos->links() }}
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay bancos registrados aún. Haz clic en "Nuevo" para agregar el primer banco.
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
                            Nuevo Banco
                        @elseif($showEdit)
                            Editar Banco
                        @endif
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($showCreate)
                        <livewire:tesoreria.banco.banco-create />
                    @elseif($showEdit)
                        <livewire:tesoreria.banco.banco-edit :bancoId="$bancoId" />
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

            window.livewire.on('bancoStore', () => {
                $('#modal').modal('hide');
            });

            window.livewire.on('bancoUpdate', () => {
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
