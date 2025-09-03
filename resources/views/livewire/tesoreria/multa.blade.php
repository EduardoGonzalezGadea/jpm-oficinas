<div>
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-0 d-inline-block">
                        Listado de Multas de Tránsito
                    </h4>
                    @if ($valorUr)
                        <span class="text-muted ml-2">(UR = $ {{ $valorUr }})</span>
                    @endif
                </div>
                <div class="col-md-4 text-right">
                    <button wire:click="create()" class="btn btn-primary d-print-none">
                        <i class="fas fa-plus"></i> Nueva Multa
                    </button>
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
                            <th class="text-center align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('articulo')">
                                    Art.
                                    @if ($sortField === 'articulo')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('apartado')">
                                    Apartado
                                    @if ($sortField === 'apartado')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('descripcion')">
                                    Descripción
                                    @if ($sortField === 'descripcion')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <!-- --- REFACTORIZADO --- -->
                            <th class="text-center align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_original')">
                                    Original
                                    @if ($sortField === 'importe_original')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center align-middle">
                                <button class="btn btn-link text-white p-0 text-nowrap" wire:click="sortBy('importe_unificado')">
                                    Unificado
                                    @if ($sortField === 'importe_unificado')
                                        <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <!-- --------------------- -->
                            <th width="150" class="text-center align-middle d-print-none">Acciones</th>
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
                                <!-- --- REFACTORIZADO --- -->
                                <td class="text-right align-middle">{!! $multa->importe_original_formateado !!}</td>
                                <td class="text-right align-middle">{!! $multa->importe_unificado_formateado !!}</td>
                                <!-- --------------------- -->

                                <td class="text-center align-middle d-print-none">
                                    <button wire:click="edit({{ $multa->id }})" class="btn btn-sm btn-warning" title="Editar"><i class="fas fa-edit"></i></button>
                                    <button onclick="confirmDelete({{ $multa->id }})" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No se encontraron multas</td>
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

    @if($isOpen)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $isEdit ? 'Editar Multa' : 'Nueva Multa' }}</h5>
                        <button type="button" class="close" wire:click="closeModal()"><span>&times;</span></button>
                    </div>

                    <form wire:submit.prevent="store">
                        <div class="modal-body" style="overflow-y: auto; max-height: 70vh; padding-right: 15px;">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="articulo">Artículo <span class="text-danger">*</span></label>
                                    <input wire:model.defer="articulo" type="text" class="form-control @error('articulo') is-invalid @enderror" id="articulo" placeholder="Ej: 103">
                                    @error('articulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="apartado">Apartado</label>
                                    <input wire:model.defer="apartado" type="text" class="form-control @error('apartado') is-invalid @enderror" id="apartado" placeholder="Ej: 2A">
                                    @error('apartado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                                <textarea wire:model.defer="descripcion" class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" rows="3" placeholder="Descripción de la multa"></textarea>
                                @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label for="moneda">Moneda <span class="text-danger">*</span></label>
                                    <select wire:model.live="moneda" class="form-control @error('moneda') is-invalid @enderror" id="moneda">
                                        <option value="UR">UR</option>
                                        <option value="USD">USD</option>
                                        <option value="UYU">UYU</option>
                                    </select>
                                    @error('moneda')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-5 form-group">
                                    <label for="importe_original">Importe Original <span class="text-danger">*</span></label>
                                    <input wire:model.defer="importe_original" type="number" step="0.01" class="form-control @error('importe_original') is-invalid @enderror" id="importe_original" placeholder="0.00">
                                    @error('importe_original')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4 form-group">
                                    <label for="importe_unificado">Importe Unificado</label>
                                    <input wire:model.defer="importe_unificado" type="number" step="0.01" class="form-control @error('importe_unificado') is-invalid @enderror" id="importe_unificado" placeholder="0.00 (opcional)">
                                    @error('importe_unificado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="decreto">Decreto</label>
                                <input wire:model.defer="decreto" type="text" class="form-control @error('decreto') is-invalid @enderror" id="decreto" placeholder="Ej: Decreto Nº 81/014">
                                @error('decreto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>


                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Actualizar' : 'Guardar' }}</button>
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
