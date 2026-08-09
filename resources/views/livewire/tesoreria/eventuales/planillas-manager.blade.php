<div class="d-print-none">
  <div class="card mt-4">
    <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
      <h3 class="mb-0">Gestión de Planillas</h3>
      <button type="button" class="btn btn-primary"
          @if($eventualesDisponiblesCount == 0) disabled @endif
onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm-with-input', { detail: { title: '¿Con qué fecha crear la planilla?', text: 'Seleccione la fecha para la nueva planilla de eventuales.', input: 'date', inputValue: '{{ date('Y-m-d') }}', method: 'createPlanilla', componentId: '{{ $this->getId() }}', confirmButtonText: 'Crear' } }))">
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
</div>

@push('scripts')
<script>
  window.addEventListener('openNewTab', event => {
    const d = window.LiveEvent(event);
    window.open(d.url, '_blank');
  });

  window.addEventListener('swal:confirm', event => {
    const d = window.LiveEvent(event);
    Swal.fire({
      title: d.title,
      text: d.text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: d.confirmButtonText,
      cancelButtonText: 'Cancelar',
      focusConfirm: true
    }).then((result) => {
      if (result.isConfirmed) {
        if (d.method === 'deletePlanilla') {
          @this.call('deletePlanilla', d.id);
        }
      }
    });
  });
</script>
@endpush
