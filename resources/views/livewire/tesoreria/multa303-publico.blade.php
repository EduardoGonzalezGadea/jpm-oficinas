<div x-data="{
    valorUr: '',
    mesUr: '',
    urVencida: false,
    loading: true,
    async fetchUr() {
        try {
            const response = await fetch('{{ route('utilidad.valor_ur') }}');
            const data = await response.json();
            this.valorUr = data.valorUr;
            this.mesUr = data.mesUr;
            this.urVencida = Boolean(data.vencido);
        } catch (error) {
            console.error('Error fetching UR:', error);
        } finally {
            this.loading = false;
        }
    }
}" x-init="fetchUr()">

    <div class="card shadow-sm">
        <div class="card-header py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-nowrap">
                    <h4 class="mb-0">
                        <strong>Códigos de Multas CPT (Dec. 303/2023)</strong>
                    </h4>
                </div>
                <div class="text-center flex-grow-1 mx-2">
                    <span class="text-white font-weight-bold" style="font-size: 1.1rem;">
                        <template x-if="!loading && valorUr">
                            <span>
                                (UR = <span x-text="valorUr"></span> <template x-if="mesUr"><span> - <span x-text="mesUr"></span><template x-if="urVencida"><span class="text-warning"> - VENCIDO</span></template></span></template>)
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
                            placeholder="Buscar por código, grupo o descripción...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-danger" type="button" wire:click="$set('search', '')" title="Limpiar filtro">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col text-left">
                    <span class="badge badge-secondary py-1 px-2 shadow-sm">
                        <i class="fas fa-info-circle mr-1"></i> Valores en UR (Dec. 303/2023)
                    </span>
                </div>
                <div class="col-auto">
                    <select wire:model="perPage" class="form-control form-control-sm">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Todos</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive" wire:loading.class="loading-overlay">
                <table class="table table-sm table-striped table-hover table-compact mb-2">
                    <thead>
                        <tr>
                            <th class="align-middle py-1 px-2">
                                <span role="button" class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('grupo')">
                                    Grupo
                                    @if ($sortField === 'grupo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('codigo')">
                                    Código
                                    @if ($sortField === 'codigo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="align-middle py-1 px-2">
                                <span role="button" class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('valor_ur')">
                                    Valor (UR)
                                    @if ($sortField === 'valor_ur')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($multas as $multa)
                        <tr>
                            <td class="align-middle py-1 px-2 small font-weight-bold text-muted">{{ $multa->grupo }}</td>
                            <td class="align-middle text-center py-1 px-2 small font-weight-bold">{{ $multa->codigo }}</td>
                            <td class="align-middle py-1 px-2 small">{{ $multa->descripcion }}</td>
                            <td class="text-center align-middle py-1 px-2 small font-weight-bold">
                                {{ $multa->valor_ur }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-3">No se encontraron códigos de multas del Decreto 303/2023</td>
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