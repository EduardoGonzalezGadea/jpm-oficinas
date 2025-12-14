<div>
    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center">
                    <h4 class="mb-0 d-inline-block mr-3">
                        Artículos de Multas de Tránsito
                    </h4>
                    <span id="ur-value-container-public" class="text-white ml-2 font-weight-bold"></span>
                </div>
                <div class="col-md-4 text-right">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Vista pública - Solo lectura
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3 align-items-center">
                <div class="col-md-5">
                    <input wire:model.debounce.500ms="search" type="text" class="form-control d-print-none"
                        placeholder="Buscar por artículo.apartado o por descripción...">
                </div>
                <div class="col d-print-none">
                    <em class="text-muted">* Unificado = a partir de Octubre/2024</em>
                </div>
                <div class="col-auto d-print-none">
                    <select wire:model="perPage" class="form-control">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Todos</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th class="align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('articulo')">
                                    Art.
                                    @if ($sortField === 'articulo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('apartado')">
                                    Apartado
                                    @if ($sortField === 'apartado')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_original')">
                                    Original
                                    @if ($sortField === 'importe_original')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_unificado')">
                                    Unificado
                                    @if ($sortField === 'importe_unificado')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($multas as $multa)
                        <tr>
                            <td class="align-middle"><strong>{{ $multa->articulo }}</strong></td>
                            <td class="align-middle">{{ $multa->apartado }}</td>
                            <td class="align-middle">
                                {{ $multa->descripcion }}
                                @if ($multa->decreto)
                                <small class="text-muted d-block">{{ $multa->decreto }}</small>
                                @endif
                            </td>
                            <td class="text-right align-middle">{!! $multa->importe_original_formateado !!}</td>
                            <td class="text-right align-middle">{!! $multa->importe_unificado_formateado !!}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No se encontraron multas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($multas instanceof \Illuminate\Pagination\LengthAwarePaginator && $multas->hasPages())
            <div class="d-flex justify-content-center mt-3 d-print-none">
                {{ $multas->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function loadURValuePublic() {
        const urContainer = document.getElementById('ur-value-container-public');
        if (urContainer) {
            urContainer.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando UR...';

            fetch("{{ route('utilidad.valor_ur') }}")
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta de la red');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.valorUr) {
                        urContainer.textContent = '(UR = ' + data.valorUr + ')';
                    } else {
                        urContainer.textContent = '(UR no disponible)';
                    }
                })
                .catch(error => {
                    console.error('Error al obtener el valor de la UR:', error);
                    urContainer.textContent = '(Error al cargar UR)';
                });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadURValuePublic();
    });

    // Recargar el valor UR cuando se actualiza el componente Livewire
    Livewire.on('updated', function() {
        setTimeout(function() {
            loadURValuePublic();
        }, 100);
    });
</script>
@endpush