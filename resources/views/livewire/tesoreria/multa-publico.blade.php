<div x-data="{
    valorUr: '',
    mesUr: '',
    loading: true,
    async fetchUr() {
        try {
            const response = await fetch('{{ route('utilidad.valor_ur') }}');
            const data = await response.json();
            this.valorUr = data.valorUr;
            this.mesUr = data.mesUr;
        } catch (error) {
            console.error('Error fetching UR:', error);
        } finally {
            this.loading = false;
        }
    }
}" x-init="fetchUr()">
    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-nowrap">
                    <h4 class="mb-0">
                        <strong>Artículos de Multas de Tránsito</strong>
                    </h4>
                </div>
                <div class="text-center flex-grow-1 mx-2">
                    <span id="ur-value-container-public" class="text-white font-weight-bold" style="font-size: 1.1rem;">
                        <template x-if="!loading && valorUr">
                            <span>
                                (UR = <span x-text="valorUr"></span> <template x-if="mesUr"><span> - <span x-text="mesUr"></span></span></template>)
                            </span>
                        </template>
                        <template x-if="loading">
                            <span>
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando UR...
                            </span>
                        </template>
                    </span>
                </div>
                <div class="text-right">
                    <small class="text-white font-weight-bold">
                        <i class="fas fa-info-circle mr-1"></i>
                        Vista pública
                    </small>
                </div>
            </div>
        </div>

        <div class="card-body p-2">
            <!-- Controles de búsqueda y filtros -->
            <div class="row mb-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <input wire:model.debounce.800ms="search" type="text" class="form-control" autofocus
                            placeholder="Buscar por artículo.apartado o por descripción...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger" type="button" wire:click="$set('search', '')" title="Limpiar filtro">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <em class="text-info font-weight-bold small">* Unificado = a partir de Octubre/2024</em>
                </div>
                <div class="col-auto">
                    <select wire:model="perPage" class="form-control form-control-sm">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Todos</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-2">
                    <thead class="thead-dark">
                        <tr>
                            <th class="align-middle py-1 px-2">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('articulo')">
                                    Art.
                                    @if ($sortField === 'articulo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle py-1 px-2">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('apartado')">
                                    Apartado
                                    @if ($sortField === 'apartado')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle py-1 px-2">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle py-1 px-2">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_original')">
                                    Original
                                    @if ($sortField === 'importe_original')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="align-middle py-1 px-2">
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
                            <td class="align-middle text-center py-1 px-2 small"><strong>{{ $multa->articulo }}</strong></td>
                            <td class="align-middle text-center py-1 px-2 small">{{ $multa->apartado }}</td>
                            <td class="align-middle py-1 px-2 small">
                                {{ $multa->descripcion }}
                                @if ($multa->decreto)
                                <small class="text-muted d-block" style="font-size: 0.75rem;">{{ $multa->decreto }}</small>
                                @endif
                            </td>
                            <td class="text-right align-middle py-1 px-2 small">{!! $multa->importe_original_formateado !!}</td>
                            <td class="text-right align-middle py-1 px-2 small">{!! $multa->importe_unificado_formateado !!}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3">No se encontraron multas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($multas, 'hasPages') && $multas->hasPages())
            <div class="d-flex justify-content-center mt-3 d-print-none">
                {{ $multas->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Carga desacoplada
</script>
@endpush
