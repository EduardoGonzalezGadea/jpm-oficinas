@props([
    'id',
    'title',
    'class' => '',
])

<div
    x-data="{
        show: false,
        close() {
            this.show = false;
            this.$dispatch('modal-closed');
            document.body.classList.remove('modal-open');
            this.$wire.closeModal('{{ $id }}');
        }
    }"
    x-init="
        Livewire.on('{{ $id }}-show', () => {
            show = true;
            document.body.classList.add('modal-open');
            document.getElementById('loader').style.display = 'none';
        });
        Livewire.on('{{ $id }}-hide', () => {
            show = false;
            document.body.classList.remove('modal-open');
            document.getElementById('loader').style.display = 'none';
        });
        $watch('show', value => {
            if (!value) {
                document.body.classList.remove('modal-open');
                document.getElementById('loader').style.display = 'none';
            }
        });
    "
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto modal-alpine"
    style="display: none;">

    <!-- Overlay de fondo -->
    <div class="fixed inset-0 bg-black opacity-50"></div>

    <!-- Modal -->
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="modal-alpine-content {{ $class }}">
            <!-- Encabezado -->
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold">
                    {{ $title }}
                </h3>
                <button
                    type="button"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center"
                    x-on:click="close">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <!-- Contenido -->
            <div class="relative">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
