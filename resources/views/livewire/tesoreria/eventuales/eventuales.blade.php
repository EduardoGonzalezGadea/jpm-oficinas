<div>
    <style>
        .text-nowrap-custom {
            white-space: nowrap;
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Eventuales</h3>
                    <div class="btn-group d-print-none">
                        <a href="{{ route('tesoreria.eventuales.instituciones') }}" class="btn btn-secondary">
                            <i class="fas fa-building"></i> Instituciones
                        </a>
                        <a href="{{ route('tesoreria.eventuales.imprimir-detalles', ['year' => $year, 'mes' => $mes]) }}" target="_blank" class="btn btn-info">
                            <i class="fas fa-print"></i> Detalles
                        </a>
                        <a href="{{ route('tesoreria.eventuales.imprimir', ['year' => $year, 'mes' => $mes]) }}" target="_blank" class="btn btn-success">
                            <i class="fas fa-print"></i> Imprimir
                        </a>
                        <button type="button" class="btn btn-primary" wire:click.prevent="create">
                            Crear Eventual
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($totalesPorInstitucion->isNotEmpty())
                        <div class="mb-3 p-3 border rounded bg-light">
                            <h5 class="mb-3 text-center">Totales por Institución</h5>
                            <div class="d-flex flex-wrap justify-content-around">
                                @foreach ($totalesPorInstitucion as $total)
                                    <div class="p-2 text-center flex-fill">
                                        <strong>{{ $total->institucion }}</strong><br>
                                        $ {{ number_format((float) $total->total_monto, 2, ',', '.') }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="year">Año</label>
                            <select wire:model="year" id="year" class="form-control form-control-sm">
                                @for ($i = date('Y'); $i >= 2020; $i--)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="mes">Mes</label>
                            <select wire:model="mes" id="mes" class="form-control form-control-sm">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}">
                                        {{ ucfirst(\Carbon\Carbon::create()->month($m)->monthName) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-print-none">
                            <label for="search">Buscar</label>
                            <input type="text" wire:model="search" id="search"
                                class="form-control form-control-sm"
                                placeholder="Buscar por ingreso, monto, O/C o recibo...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Fecha</th>
                                    <th class="text-center align-middle">Ingreso</th>
                                    <th class="text-center align-middle">Institución</th>
                                    <th class="text-center align-middle">Monto</th>
                                    <th class="text-center align-middle">O/C</th>
                                    <th class="text-center align-middle">Recibo</th>
                                    <th class="text-center align-middle">Medio de Pago</th>
                                    @canany(['gestionar_tesoreria', 'supervisar_tesoreria'])
                                        <th class="text-center align-middle d-print-none">Confirmado</th>
                                    @endcanany
                                    <th class="text-center align-middle d-print-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($eventuales as $eventual)
                                    <tr>
                                        <td class="text-center align-middle">{{ $eventual->fecha->format('d/m/Y') }}
                                        </td>
                                        <td class="text-right align-middle">
                                            {{ is_numeric($eventual->ingreso) ? number_format($eventual->ingreso, 0, ',', '.') : $eventual->ingreso }}</td>
                                        <td class="text-center align-middle">{{ $eventual->institucion }}</td>
                                        <td class="text-right align-middle"><span
                                                class="text-nowrap-custom">{{ $eventual->monto_formateado }}</span></td>
                                        <td class="text-right align-middle">
                                            {{ is_numeric($eventual->orden_cobro) ? number_format($eventual->orden_cobro, 0, ',', '.') : $eventual->orden_cobro }}</td>
                                        <td class="text-right align-middle">{{ is_numeric($eventual->recibo) ? number_format($eventual->recibo, 0, ',', '.') : $eventual->recibo }}</td>
                                        <td class="text-center align-middle">{{ $eventual->medio_de_pago }}</td>
                                        @canany(['gestionar_tesoreria', 'supervisar_tesoreria'])
                                            <td
                                                class="text-center{{ !$eventual->confirmado ? ' table-warning' : '' }} align-middle d-print-none">
                                                <div class="custom-control custom-switch" style="transform: scale(0.8);">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="confirmado-{{ $eventual->id }}"
                                                        wire:click.prevent="toggleConfirmado({{ $eventual->id }})"
                                                        {{ $eventual->confirmado ? 'checked' : '' }}>
                                                    <label class="custom-control-label"
                                                        for="confirmado-{{ $eventual->id }}"></label>
                                                </div>
                                            </td>
                                        @endcanany
                                        <td class="text-center align-middle d-print-none">
                                            <button wire:click="showDetails({{ $eventual->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsModal" title="Detalles"><i
                                                    class="fas fa-eye"></i></button>
                                            <button wire:click="edit({{ $eventual->id }})"
                                                class="btn btn-sm btn-primary" title="Editar"><i
                                                    class="fas fa-edit"></i></button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $eventual->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar"><i
                                                    class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="@canany(['gestionar_tesoreria', 'supervisar_tesoreria']) 9 @else 8 @endcanany"
                                            class="text-center">No hay registros para el mes y año seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                @foreach ($subtotales as $subtotal)
                                    <tr>
                                        <td colspan="3" class="text-right align-middle"><strong>Total
                                                {{ $subtotal->medio_de_pago }}:</strong></td>
                                        <td class="text-right align-middle"><strong><span class="text-nowrap-custom">$
                                                    {{ number_format($subtotal->total_submonto, 2, ',', '.') }}</span></strong>
                                        </td>
                                        <td colspan="@canany(['gestionar_tesoreria', 'supervisar_tesoreria']) 5 @else 4 @endcanany"
                                            class="align-middle"></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3" class="text-right align-middle"><strong>Total General:</strong>
                                    </td>
                                    <td class="text-right align-middle"><strong><span class="text-nowrap-custom">$
                                                {{ number_format($generalTotal, 2, ',', '.') }}</span></strong></td>
                                    <td
                                        colspan="@canany(['gestionar_tesoreria', 'supervisar_tesoreria']) 5 @else 4 @endcanany">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $eventuales->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div wire:ignore.self class="modal fade" id="eventualModal" tabindex="-1" role="dialog"
        aria-labelledby="eventualModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventualModalLabel">{{ $eventual_id ? 'Editar' : 'Crear' }} Eventual
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" wire:model.defer="fecha" id="fecha"
                                        class="form-control form-control-sm">
                                    @error('fecha')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ingreso">Ingreso</label>
                                    <input type="text" wire:model.defer="ingreso" id="ingreso"
                                        class="form-control form-control-sm">
                                    @error('ingreso')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="institucion">Institución</label>
                                    <select wire:model.defer="institucion" id="institucion"
                                        class="form-control form-control-sm">
                                        <option value="">Seleccione...</option>
                                        @foreach($instituciones as $inst)
                                            <option value="{{ $inst->nombre }}">{{ $inst->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('institucion')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titular">Titular</label>
                                    <input type="text" wire:model.defer="titular" id="titular"
                                        class="form-control form-control-sm">
                                    @error('titular')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="medio_de_pago">Medio de Pago</label>
                                    <select wire:model.defer="medio_de_pago" id="medio_de_pago"
                                        class="form-control form-control-sm">
                                        <option value="">Seleccione...</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia">Transferencia</option>
                                        <option value="POS">POS</option>
                                        <option value="Cheque">Cheque</option>
                                    </select>
                                    @error('medio_de_pago')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="monto">Monto</label>
                                    <input type="number" step="0.01" wire:model.defer="monto" id="monto"
                                        class="form-control form-control-sm">
                                    @error('monto')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="detalle">Detalle</label>
                            <textarea wire:model.defer="detalle" id="detalle" class="form-control form-control-sm"></textarea>
                            @error('detalle')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="orden_cobro">Orden de Cobro</label>
                                    <input type="text" wire:model.defer="orden_cobro" id="orden_cobro"
                                        class="form-control form-control-sm">
                                    @error('orden_cobro')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="recibo">Recibo</label>
                                    <input type="text" wire:model.defer="recibo" id="recibo"
                                        class="form-control form-control-sm">
                                    @error('recibo')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click.prevent="{{ $eventual_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">{{ $eventual_id ? 'Actualizar' : 'Guardar' }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog"
        aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Detalles del Eventual</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedEventual)
                        <p class="mb-0"><strong>Fecha:</strong> {{ $selectedEventual->fecha->format('d/m/Y') }}</p>
                        <p class="mb-0"><strong>Ingreso:</strong> {{ $selectedEventual->ingreso }}</p>
                        <p class="mb-0"><strong>Institución:</strong> {{ $selectedEventual->institucion }}</p>
                        <p class="mb-0"><strong>Titular:</strong> {{ $selectedEventual->titular }}</p>
                        <p class="mb-0"><strong>Medio de Pago:</strong> {{ $selectedEventual->medio_de_pago }}</p>
                        <p class="mb-0"><strong>Monto:</strong> <span
                                class="text-nowrap-custom">{{ $selectedEventual->monto_formateado }}</span></p>
                        <p class="mb-0"><strong>Detalle:</strong> {{ $selectedEventual->detalle }}</p>
                        <p class="mb-0"><strong>Orden de Cobro:</strong> {{ $selectedEventual->orden_cobro }}</p>
                        <p class="mb-0"><strong>Recibo:</strong> {{ $selectedEventual->recibo }}</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        wire:click="resetDetails()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <livewire:tesoreria.eventuales.planillas-manager :mes="$mes" :year="$year" :key="$mes . $year" />

    @push('scripts')
        <script>
            window.addEventListener('swal:confirm', event => {
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
                    if (result.isConfirmed) {
                        @this.call(event.detail.method, event.detail.id);
                    }
                });
            });

            window.addEventListener('revertCheckbox', event => {
                const checkbox = document.getElementById('confirmado-' + event.detail.id);
                if (checkbox) {
                    checkbox.checked = event.detail.checked;
                }
            });

            window.addEventListener('close-modal', event => {
                $('#eventualModal').modal('hide');
            });

            window.addEventListener('alert', event => {
                const type = event.detail.type;
                const message = event.detail.message;
                const isToast = event.detail.toast || false; // Get toast property, default to false

                if (isToast) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end', // Position for toast
                        showConfirmButton: false,
                        timer: 3000, // Longer timer for toast
                        timerProgressBar: true,
                        icon: type,
                        title: message,
                    });
                } else {
                    Swal.fire({
                        icon: type,
                        title: message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });

            // Manejar redirección cuando el JWT expire
            window.addEventListener('redirect-to-login', event => {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesión Expirada',
                    text: event.detail.message,
                    showConfirmButton: true,
                    confirmButtonText: 'Ir al Login',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Limpiar tokens locales si existieran
                        try {
                            localStorage.removeItem('jwt_token');
                            sessionStorage.removeItem('jwt_token');
                        } catch (e) {}
                        
                        // Redirigir al login
                        window.location.href = '{{ route("login") }}';
                    }
                });
            });

            window.livewire.on('eventualStore', () => {
                $('#eventualModal').modal('hide');
            });

            window.livewire.on('eventualUpdate', () => {
                $('#eventualModal').modal('hide');
            });

            $(document).ready(function() {
                $('#eventualModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });

                $('#eventualModal').on('shown.bs.modal', function() {
                    $('#ingreso').focus();

                    const form = $(this).find('form');
                    const inputs = form.find('input:not([type="hidden"]), textarea, select');

                    inputs.off('keydown').on('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();

                            const currentIndex = inputs.index(this);
                            const nextIndex = currentIndex + 1;

                            if (nextIndex < inputs.length) {
                                $(inputs[nextIndex]).focus();
                            } else {
                                form.closest('.modal-content').find('.btn-primary').focus();
                            }
                        }
                    });
                });
            });
        </script>
    @endpush

</div>
