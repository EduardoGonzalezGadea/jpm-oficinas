<div class="d-print-none">
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Gestión de Planillas</h3>
            <button type="button" class="btn btn-success" wire:click="createPlanilla"
                    @if($eventualesDisponiblesCount == 0) disabled @endif>
                Crear Nueva Planilla ({{ $eventualesDisponiblesCount }} eventuales disponibles)
            </button>
        </div>
        <div class="card-body">
            @if ($planillas->isEmpty())
                <p class="text-center">No hay planillas creadas aún.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th class="text-center align-middle">Número de Planilla</th>
                                <th class="text-center align-middle">Fecha de Creación</th>
                                <th class="text-center align-middle">Creada Por</th>
                                <th class="text-center align-middle">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($planillas as $planilla)
                                <tr>
                                    <td class="text-center align-middle">{{ $planilla->numero }}</td>
                                    <td class="text-center align-middle">{{ $planilla->fecha_creacion->format('d/m/Y') }}</td>
                                    <td class="text-center align-middle">{{ $planilla->user->nombre_completo ?? 'N/A' }}</td>
                                    <td class="text-center align-middle">
                                        <button wire:click="printPlanilla({{ $planilla->id }})" class="btn btn-sm btn-info" title="Imprimir Planilla">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <button onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'deletePlanilla', id: {{ $planilla->id }}, confirmButtonText: 'Sí, elimínala' } }))" class="btn btn-sm btn-danger" title="Eliminar Planilla">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        window.addEventListener('openNewTab', event => {
            window.open(event.detail.url, '_blank');
        });

        window.addEventListener('swal:confirm', event => {
            console.log('swal:confirm event received', event.detail); // Log the entire detail object
            Swal.fire({
                title: event.detail.title,
                text: event.detail.text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: event.detail.confirmButtonText,
                cancelButtonText: 'Cancelar',
                focusConfirm: true
            }).then((result) => {
                console.log('Swal result:', result); // Log the result object
                if (result.isConfirmed) {
                    console.log('User confirmed. Method:', event.detail.method); // Log the method
                    if (event.detail.method === 'deletePlanilla') {
                        console.log('Calling deletePlanilla with ID:', event.detail.id);
                        @this.call('deletePlanilla', event.detail.id);
                    }
                }
            });
        });
    </script>
    @endpush
</div>
