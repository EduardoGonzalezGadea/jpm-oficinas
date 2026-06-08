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

    <div class="card">
        <div class="card-header bg-dark text-white card-header-gradient py-2 px-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-nowrap">
                    <h4 class="mb-0">
                        <strong>Multas de Tránsito Dec. 303/2023</strong>
                    </h4>
                </div>
                <div class="text-center flex-grow-1 mx-2">
                    <span id="ur-value-container" class="text-white font-weight-bold" style="font-size: 1.1rem;">
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
                <div class="d-print-none text-right">
                    <button wire:click="create()" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Nueva Multa 303
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-2">
            <!-- Controles de búsqueda -->
            <div class="row mb-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <input wire:model.debounce.800ms="search" type="text" class="form-control d-print-none" autofocus
                            placeholder="Buscar por código, grupo o descripción...">
                        <div class="input-group-append d-print-none">
                            <button class="btn btn-outline-danger" type="button" wire:click="$set('search', '')" title="Limpiar filtro">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col d-print-none text-left">
                    <span class="badge badge-secondary py-1 px-2 shadow-sm">
                        <i class="fas fa-info-circle mr-1"></i> Valores en UR (Dec. 303/2023)
                    </span>
                    <span class="badge badge-secondary py-1 px-2 shadow-sm">
                        <i class="fas fa-info-circle mr-1"></i> Desde el 18/05/2026
                    </span>
                </div>
                <div class="col-auto d-print-none">
                    <select wire:model="perPage" class="form-control form-control-sm">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">Todos</option>
                    </select>
                </div>
            </div>

            <!-- Loader de Livewire -->
            <div wire:loading wire:target="search, perPage, sortBy, delete" class="text-center py-4">
                <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>

            @if((isset($multas) && $multas->isNotEmpty()) || !empty($search))
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-2">
                    <thead class="thead-dark">
                        <tr>
                            <th class="align-middle py-1 px-2" style="width: 20%;">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('grupo')">
                                    Grupo
                                    @if ($sortField === 'grupo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2" style="width: 10%;">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('codigo')">
                                    Código
                                    @if ($sortField === 'codigo')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="align-middle py-1 px-2" style="width: 50%;">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="text-center align-middle py-1 px-2" style="width: 10%;">
                                <span role="button" class="text-white text-nowrap" wire:click="sortBy('valor_ur')">
                                    Valor (UR)
                                    @if ($sortField === 'valor_ur')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th width="100" class="text-center align-middle py-1 px-2 d-print-none">Acciones</th>
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
                            <td class="text-center align-middle py-1 px-2 d-print-none" style="white-space: nowrap;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button wire:click="edit({{ $multa->id }})" class="btn btn-warning" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirmDelete303({{ $multa->id }})" class="btn btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3">No se encontraron multas del Decreto 303/2023</td>
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
                <div class="modal-content-border">
                    <div class="modal-header py-2 bg-dark text-white">
                        <h5 class="modal-title mb-0">{{ $isEdit ? 'Editar Multa (Dec. 303/2023)' : 'Nueva Multa (Dec. 303/2023)' }}</h5>
                        <button type="button" class="close text-white" wire:click="closeModal()"><span>&times;</span></button>
                    </div>

                    <form wire:submit.prevent="store">
                        <div class="modal-body py-3" style="overflow-y: auto; max-height: 70vh;">
                            <div class="row">
                                <div class="col-md-8 form-group mb-2">
                                    <label for="grupo" class="mb-1 small"><strong>Grupo de la Multa <span class="text-danger">*</span></strong></label>
                                    <input wire:model.defer="grupo" type="text" list="grupos-existentes" class="form-control form-control-sm @error('grupo') is-invalid @enderror" id="grupo" placeholder="Ej: Del uso de la vía pública">
                                    <datalist id="grupos-existentes">
                                        @foreach($grupos as $g)
                                            <option value="{{ $g }}">
                                        @endforeach
                                    </datalist>
                                    @error('grupo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4 form-group mb-2">
                                    <label for="codigo" class="mb-1 small"><strong>Código <span class="text-danger">*</span></strong></label>
                                    <input wire:model.defer="codigo" type="text" class="form-control form-control-sm @error('codigo') is-invalid @enderror" id="codigo" placeholder="Ej: 2.2">
                                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-group mb-2">
                                <label for="descripcion" class="mb-1 small"><strong>Descripción <span class="text-danger">*</span></strong></label>
                                <textarea wire:model.defer="descripcion" class="form-control form-control-sm @error('descripcion') is-invalid @enderror" id="descripcion" rows="3" placeholder="Descripción detallada de la infracción"></textarea>
                                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-2">
                                    <label for="valor_ur" class="mb-1 small"><strong>Valor en UR <span class="text-danger">*</span></strong></label>
                                    <input wire:model.defer="valor_ur" type="text" class="form-control form-control-sm @error('valor_ur') is-invalid @enderror" id="valor_ur" placeholder="Ej: 3 o 2 x c/u">
                                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Permite texto libre para casos especiales (ej: "2 x c/u", "Ver Cuadro 1").</small>
                                    @error('valor_ur')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
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
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('scripts')
<script>
    function confirmDelete303(id) {
        Swal.fire({
            title: '¿Está seguro de eliminar esta multa?',
            text: "¡No podrá revertir esto! Se realizará un borrado lógico.",
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
