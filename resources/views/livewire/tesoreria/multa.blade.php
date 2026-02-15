<div x-data="{
    valorUr: '',
    mesUr: '',
    loading: true,
    soaLoading: false,
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
    },
    async actualizarSoa() {
        if (this.soaLoading) return;
        this.soaLoading = true;
        try {
            const response = await fetch('{{ route('utilidad.actualizar-soa') }}');
            const data = await response.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    timer: 3000
                });
                // Refrescar listado en Livewire
                @this.call('refreshList');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || 'No se pudieron actualizar los valores SOA.'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'Hubo un problema al conectar con el servidor.'
            });
        } finally {
            this.soaLoading = false;
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
                    <span id="ur-value-container" class="text-white font-weight-bold" style="font-size: 1.1rem;">
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
                <div class="d-print-none text-right">
                    <a href="{{ route('tesoreria.multas-transito.exportar-pdf') }}" target="_blank" class="btn btn-danger btn-sm shadow-sm mr-2">
                        <i class="fas fa-file-pdf mr-1"></i> Descargar PDF
                    </a>
                    <button wire:click="create()" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Nueva Multa
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-2">
            <!-- Mostrar controles de búsqueda inmediatamente -->
            <div class="row mb-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <input wire:model.debounce.800ms="search" type="text" class="form-control d-print-none" autofocus
                            placeholder="Buscar por artículo.apartado o por descripción...">
                        <div class="input-group-append d-print-none">
                            <button class="btn btn-outline-danger" type="button" wire:click="$set('search', '')" title="Limpiar filtro">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col d-print-none">
                    <em class="text-muted">* Unificado = a partir de Octubre/2024</em>
                </div>
                <div class="col-auto d-print-none text-right">
                    <button @click="actualizarSoa()" :disabled="soaLoading" class="btn btn-warning btn-sm mr-2 text-white" title="Actualizar valores SOA desde BCU">
                        <span x-show="soaLoading" class="mr-1">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                        <span x-show="!soaLoading" class="mr-1">
                            <i class="fas fa-sync-alt"></i>
                        </span>
                        <span class="d-none d-xl-inline" x-text="soaLoading ? 'Actualizando...' : 'Actualizar SOA'"></span>
                    </button>
                </div>
                <div class="col-auto d-print-none">
                    <select wire:model="perPage" class="form-control form-control-sm">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Todos</option>
                    </select>
                </div>
            </div>

            <!-- Loader mientras se cargan las multas automáticamente -->
            <div wire:loading wire:target="loadMultasAutomaticamente" class="text-center py-4">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Cargando...</span>
                </div>
                <div class="mt-3">
                    <h6 class="text-muted">Cargando listado de multas...</h6>
                    <small class="text-muted">Esto puede tomar unos segundos</small>
                </div>
            </div>

            @if((isset($multas) && $multas->isNotEmpty()) || !empty($search))
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-2">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('articulo')">
                                    Art.
                                    @if ($sortField === 'articulo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('apartado')">
                                    Apartado
                                    @if ($sortField === 'apartado')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('importe_original')">
                                    Original
                                    @if ($sortField === 'importe_original')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('importe_unificado')">
                                    Unificado
                                    @if ($sortField === 'importe_unificado')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th width="120" class="text-center align-middle py-1 px-2 d-print-none">Acciones</th>
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

                            <td class="text-center align-middle py-1 px-2 d-print-none" style="white-space: nowrap;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button wire:click="edit({{ $multa->id }})" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirmDelete({{ $multa->id }})" class="btn btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3">No se encontraron multas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            @if((isset($multas) && $multas->isNotEmpty()) || !empty($search))
            @if (method_exists($multas, 'hasPages') && $multas->hasPages())
            <div class="d-flex justify-content-center mt-3 d-print-none">
                {{ $multas->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

    @if($isOpen)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title mb-0">{{ $isEdit ? 'Editar Multa' : 'Nueva Multa' }}</h5>
                    <button type="button" class="close" wire:click="closeModal()"><span>&times;</span></button>
                </div>

                <form wire:submit.prevent="store">
                    <div class="modal-body py-3" style="overflow-y: auto; max-height: 70vh;">
                        <div class="row">
                            <div class="col-md-6 form-group mb-2">
                                <label for="articulo" class="mb-1 small"><strong>Artículo <span class="text-danger">*</span></strong></label>
                                <input wire:model.defer="articulo" type="text" class="form-control form-control-sm @error('articulo') is-invalid @enderror" id="articulo" placeholder="Ej: 103">
                                @error('articulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 form-group mb-2">
                                <label for="apartado" class="mb-1 small"><strong>Apartado</strong></label>
                                <input wire:model.defer="apartado" type="text" class="form-control form-control-sm @error('apartado') is-invalid @enderror" id="apartado" placeholder="Ej: 2A">
                                @error('apartado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="descripcion" class="mb-1 small"><strong>Descripción <span class="text-danger">*</span></strong></label>
                            <textarea wire:model.defer="descripcion" class="form-control form-control-sm @error('descripcion') is-invalid @enderror" id="descripcion" rows="2" placeholder="Descripción de la multa"></textarea>
                            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-3 form-group mb-2">
                                <label for="moneda" class="mb-1 small"><strong>Moneda <span class="text-danger">*</span></strong></label>
                                <select wire:model="moneda" class="form-control form-control-sm @error('moneda') is-invalid @enderror" id="moneda">
                                    <option value="UR">UR</option>
                                    <option value="USD">USD</option>
                                    <option value="UYU">UYU</option>
                                </select>
                                @error('moneda')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5 form-group mb-2">
                                <label for="importe_original" class="mb-1 small"><strong>Importe Original <span class="text-danger">*</span></strong></label>
                                <input wire:model.defer="importe_original" type="number" step="0.01" class="form-control form-control-sm @error('importe_original') is-invalid @enderror" id="importe_original" placeholder="0.00">
                                @error('importe_original')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 form-group mb-2">
                                <label for="importe_unificado" class="mb-1 small"><strong>Importe Unificado</strong></label>
                                <input wire:model.defer="importe_unificado" type="number" step="0.01" class="form-control form-control-sm @error('importe_unificado') is-invalid @enderror" id="importe_unificado" placeholder="0.00 (opcional)">
                                @error('importe_unificado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="decreto" class="mb-1 small"><strong>Decreto</strong></label>
                            <input wire:model.defer="decreto" type="text" class="form-control form-control-sm @error('decreto') is-invalid @enderror" id="decreto" placeholder="Ej: Decreto Nº 81/014">
                            @error('decreto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>


                    </div>

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="store">
                            <span wire:loading.remove wire:target="store">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</span>
                            <span wire:loading wire:target="store">{{ $isEdit ? 'Actualizando...' : 'Guardando...' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('scripts')
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: "¡No podrá revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ¡eliminar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('delete', id);
            }
        })
    }
</script>
@endpush
