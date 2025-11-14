@extends('layouts.app')

@section('content')
<livewire:tesoreria.banco.banco-index />

<!-- Modal -->
<div wire:ignore.self class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">
                    @if(isset($showCreate) && $showCreate)
                        Nuevo Banco
                    @elseif(isset($showEdit) && $showEdit)
                        Editar Banco
                    @endif
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if(isset($showCreate) && $showCreate)
                    <livewire:tesoreria.banco.banco-create />
                @elseif(isset($showEdit) && $showEdit)
                    <livewire:tesoreria.banco.banco-edit :bancoId="$bancoId" />
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:loaded', () => {
    Livewire.on('show-modal', () => {
        $('#modal').modal('show');
    });

    Livewire.on('close-modal', () => {
        $('#modal').modal('hide');
    });
});
</script>
@endsection
