<div>
    <div class="card">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Cobros de Caja Diaria - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h5>
                <button type="button" class="btn btn-primary" wire:click.prevent="create"
                    data-toggle="modal" data-target="#cobroModal">
                    Nuevo Cobro
                </button>
            </div>
        </div>
        <div class="card-body">
            @if (session()->has('message'))
                <div class="alert alert-success">{{ session('message') }}</div>
            @endif


            @if (count($cobrosAgrupados) > 0)
                @foreach ($cobrosAgrupados as $conceptoNombre => $cobrosPorMedioPago)
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tag mr-2"></i>
                                {{ $conceptoNombre }}
                            </h5>
                        </div>
                        <div class="card-body">
                            @foreach ($cobrosPorMedioPago as $medioPagoNombre => $cobros)
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="font-weight-bold">
                                            <i class="fas fa-credit-card mr-2"></i>
                                            Medio de Pago: {{ $medioPagoNombre }}
                                        </span>
                                        <span class="badge badge-info">{{ count($cobros) }} registro(s)</span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Recibo</th>
                                                    <th>Monto</th>
                                                    <th>Descripción</th>
                                                    <th>Otros</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($cobros as $cobro)
                                                    <tr>
                                                        <td class="align-middle">{{ $cobro->recibo ?: '-' }}</td>
                                                        <td class="font-weight-bold align-middle">$ {{ number_format($cobro->monto, 2, ',', '.') }}</td>
                                                        <td class="align-middle">{{ $cobro->descripcion }}</td>
                                                        <td class="align-middle">
                                                            @if ($cobro->campoValores->count() > 0)
                                                                <small>
                                                                    @foreach ($cobro->campoValores as $valor)
                                                                        <strong>{{ $valor->campo->titulo ?: $valor->campo->nombre }}:</strong> {{ $valor->valor }}<br>
                                                                    @endforeach
                                                                </small>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="align-middle">
                                                            <button wire:click="edit({{ $cobro->id }})" class="btn btn-sm btn-primary" title="Editar cobro">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button wire:click="delete({{ $cobro->id }})" class="btn btn-sm btn-danger" title="Borrar cobro">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-active">
                                                    <td class="font-weight-bold">Total {{ $medioPagoNombre }}:</td>
                                                    <td class="font-weight-bold">
                                                        $ {{ number_format(collect($cobros)->sum('monto'), 2, ',', '.') }}
                                                    </td>
                                                    <td colspan="3"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Total por concepto -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <strong>Total {{ $conceptoNombre }}:</strong>
                                        $ {{ number_format(collect($cobrosPorMedioPago)->flatten(1)->sum('monto'), 2, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    No hay cobros registrados para esta fecha.
                </div>
            @endif
        </div>
    </div>

    <!-- Modal para Confirmar Borrado -->
    @if ($confirmingCobroDeletion)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Borrado</h5>
                        <button wire:click="cancelDelete()" type="button" class="close" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que deseas eliminar este cobro?</p>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="cancelDelete()" type="button" class="btn btn-secondary">Cancelar</button>
                        <button wire:click.prevent="deleteCobro()" type="button" class="btn btn-danger">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal para Nuevo Cobro -->
    <div wire:ignore.self class="modal fade" id="cobroModal" tabindex="-1" role="dialog"
        aria-labelledby="cobroModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ isset($cobro_id) && $cobro_id ? 'Editar' : 'Nuevo' }} Cobro</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="concepto_id">Concepto *</label>
                                        <select class="form-control focus-next" id="concepto_id" wire:model="concepto_id">
                                            <option value="">Seleccione un concepto</option>
                                            @foreach ($conceptos as $concepto)
                                                <option value="{{ $concepto['id'] }}">{{ $concepto['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('concepto_id') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="recibo">Recibo</label>
                                        <input type="number" class="form-control focus-next" id="recibo" wire:model="recibo" placeholder="Número de recibo">
                                        @error('recibo') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="medio_pago">Medio de Pago *</label>
                                        <select class="form-control focus-next" id="medio_pago" wire:model="medio_pago_id">
                                            <option value="">Seleccione medio de pago</option>
                                            @foreach ($mediosPago as $medio)
                                                <option value="{{ $medio['id'] }}">{{ $medio['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('medio_pago') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="monto">Monto *</label>
                                        <input type="number" step="0.01" class="form-control focus-next" id="monto" wire:model="monto">
                                        @error('monto') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion">Descripción</label>
                                        <input type="text" class="form-control focus-next" id="descripcion" wire:model="descripcion">
                                        @error('descripcion') <span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Campos dinámicos del concepto -->
                            @if ($conceptoSeleccionado)
                                <div class="row">
                                    @foreach ($camposDinamicos as $campo)
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label for="campo_{{ $campo['id'] }}">
                                                    {{ $campo['titulo'] ?: $campo['nombre'] }}
                                                    @if ($campo['requerido']) <span class="text-danger">*</span> @endif
                                                </label>
                                                @if ($campo['tipo'] === 'text')
                                                    <input type="text" class="form-control focus-next" id="campo_{{ $campo['id'] }}" wire:model="camposValores.{{ $campo['id'] }}">
                                                @elseif ($campo['tipo'] === 'number')
                                                    <input type="number" step="0.01" class="form-control focus-next" id="campo_{{ $campo['id'] }}" wire:model="camposValores.{{ $campo['id'] }}">
                                                @elseif ($campo['tipo'] === 'date')
                                                    <input type="date" class="form-control focus-next" id="campo_{{ $campo['id'] }}" wire:model="camposValores.{{ $campo['id'] }}">
                                                @elseif ($campo['tipo'] === 'textarea')
                                                    <textarea class="form-control focus-next" id="campo_{{ $campo['id'] }}" wire:model="camposValores.{{ $campo['id'] }}" rows="3"></textarea>
                                                @elseif ($campo['tipo'] === 'select' && $campo['opciones'])
                                                    <select class="form-control focus-next" id="campo_{{ $campo['id'] }}" wire:model="camposValores.{{ $campo['id'] }}">
                                                        <option value="">Seleccione...</option>
                                                        @foreach ($campo['opciones'] as $opcion)
                                                            <option value="{{ $opcion }}">{{ $opcion }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif ($campo['tipo'] === 'checkbox')
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input focus-next" id="campo_{{ $campo['id'] }}" wire:model="camposValores.{{ $campo['id'] }}">
                                                        <label class="form-check-label" for="campo_{{ $campo['id'] }}">{{ $campo['nombre'] }}</label>
                                                    </div>
                                                @endif
                                                @error('camposValores.' . $campo['id']) <span class="text-danger">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button wire:click.prevent="store()" type="button" class="btn btn-primary" id="btn-guardar">{{ isset($cobro_id) && $cobro_id ? 'Actualizar' : 'Guardar' }} Cobro</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#cobroModal').on('shown.bs.modal', function() {
            $('#concepto_id').focus();

            const form = $(this).find('form');
            let inputs = form.find('input:not([type="hidden"]), textarea, select');

            inputs.off('keydown').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    const currentIndex = inputs.index(this);
                    const nextIndex = currentIndex + 1;

                    if (nextIndex < inputs.length) {
                        $(inputs[nextIndex]).focus();
                    } else {
                        // Click the primary button in the footer
                        form.closest('.modal-content').find('.modal-footer .btn-primary').focus().click();
                    }
                }
            });

            // Re-bind enter navigation when dynamic fields are added
            window.livewire.on('dynamicFieldsLoaded', () => {
                inputs = form.find('input:not([type="hidden"]), textarea, select');
                inputs.off('keydown').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        const currentIndex = inputs.index(this);
                        const nextIndex = currentIndex + 1;

                        if (nextIndex < inputs.length) {
                            $(inputs[nextIndex]).focus();
                        } else {
                            // Click the primary button in the footer
                            form.closest('.modal-content').find('.modal-footer .btn-primary').focus().click();
                        }
                    }
                });
            });
        });

        // Reset form on modal close
        $('#cobroModal').on('hidden.bs.modal', function() {
            window.livewire.emit('resetForm');
        });

        window.livewire.on('cobroStore', () => {
            $('#cobroModal').modal('hide');
        });

        window.addEventListener('show-cobro-modal', event => {
            $('#cobroModal').modal('show');
        });
    });
</script>
@endpush
</div>
