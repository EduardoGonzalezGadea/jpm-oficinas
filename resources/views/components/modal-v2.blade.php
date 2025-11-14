@props([
    'id',
    'title',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <!-- Encabezado -->
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}Label">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Contenido -->
            <div class="modal-body">
                {{ $slot }}
            </div>

            <!-- Pie de página dinámico -->
            @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>

<style>
    .modal-backdrop {
        z-index: 1040;
    }
    .modal {
        z-index: 1050;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.addEventListener('{{ $id }}-show', function () {
            $('#{{ $id }}').modal({
                backdrop: 'static', // Evitar que se cierre al hacer clic fuera
                keyboard: false    // Evitar que se cierre con la tecla ESC
            });
        });

        window.addEventListener('{{ $id }}-hide', function () {
            $('#{{ $id }}').modal('hide');
        });

        // Eliminar cierre automático del modal principal
        $('.modal').on('hidden.bs.modal', function (e) {
            const nextModalEvent = e.target.getAttribute('data-next-modal-event');
            if (nextModalEvent && e.target.id !== '{{ $id }}') {
                window.dispatchEvent(new Event(nextModalEvent));
            }
        });
    });
</script>
