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
                    <h3 class="mb-0">Arrendamientos</h3>
                    <div class="btn-group d-print-none">
                        <a href="{{ route('tesoreria.arrendamientos.imprimir-todo', ['year' => $year, 'mes' => $mes]) }}" target="_blank" class="btn btn-info">
                            <i class="fas fa-print"></i> Detalles
                        </a>
                        <a href="{{ route('tesoreria.arrendamientos.imprimir', ['year' => $year, 'mes' => $mes]) }}" target="_blank" class="btn btn-success">
                            <i class="fas fa-print"></i> Imprimir
                        </a>
                        <button type="button" class="btn btn-primary" wire:click.prevent="create"
                            data-toggle="modal" data-target="#arrendamientoModal">
                            Crear Arrendamiento
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Selector de Fecha/Mes/Año -->
                    <div class="form-row mb-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Mes y Año</span>
                                </div>
                                <select id="mesSelector" class="form-control" wire:model.live="mes">
                                    <option value="1">Enero</option>
                                    <option value="2">Febrero</option>
                                    <option value="3">Marzo</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Mayo</option>
                                    <option value="6">Junio</option>
                                    <option value="7">Julio</option>
                                    <option value="8">Agosto</option>
                                    <option value="9">Septiembre</option>
                                    <option value="10">Octubre</option>
                                    <option value="11">Noviembre</option>
                                    <option value="12">Diciembre</option>
                                </select>
                                <input type="number" id="anioSelector" class="form-control" wire:model.live="year">
                            </div>
                        </div>
                        <div class="col-md-7 align-self-end d-print-none">
                            <div class="btn-group d-flex" role="group">
                                <div class="input-group">
                                    <input type="text" wire:model="search" id="search"
                                        class="form-control form-control-sm"
                                        placeholder="Buscar por ingreso, monto, O/C o recibo...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center align-middle">Fecha</th>
                                    <th class="text-center align-middle">Ingreso</th>
                                    <th class="text-center align-middle">Nombre</th>
                                    <th class="text-center align-middle">Monto</th>
                                    <th class="text-center align-middle">O/C</th>
                                    <th class="text-center align-middle">Recibo</th>
                                    <th class="text-center align-middle">Medio de Pago</th>
                                    <th class="text-center align-middle"></th>
                                    @canany(['gestionar_tesoreria', 'supervisar_tesoreria'])
                                        <th class="text-center align-middle d-print-none">Confirmado</th>
                                    @endcanany
                                    <th class="text-center align-middle d-print-none">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($arrendamientos as $arrendamiento)
                                    <tr>
                                        <td class="text-center align-middle">
                                            {{ $arrendamiento->fecha->format('d/m/Y') }}</td>
                                        <td class="text-right align-middle{{ is_null($arrendamiento->ingreso) || $arrendamiento->ingreso == 0 ? ' table-warning' : '' }}">
                                            {{ is_numeric($arrendamiento->ingreso) ? number_format($arrendamiento->ingreso, 0, ',', '.') : $arrendamiento->ingreso }}</td>
                                        <td class="text-left align-middle">{{ $arrendamiento->nombre }}</td>
                                        <td class="text-right align-middle"><span
                                                class="text-nowrap-custom">{{ $arrendamiento->monto_formateado }}</span>
                                        </td>
                                        <td class="text-right align-middle">
                                            {{ is_numeric($arrendamiento->orden_cobro) ? number_format($arrendamiento->orden_cobro, 0, ',', '.') : $arrendamiento->orden_cobro }}</td>
                                        <td class="text-right align-middle">
                                            {{ is_numeric($arrendamiento->recibo) ? number_format($arrendamiento->recibo, 0, ',', '.') : $arrendamiento->recibo }}</td>
                                        <td class="text-center align-middle">{{ $arrendamiento->medio_de_pago }}</td>
                                        <td class="text-center align-middle">
                                            @if($arrendamiento->planilla_id)
                                                <i class="fas fa-check-circle text-success" title="En planilla"></i>
                                            @else
                                                <i class="fas fa-times-circle text-danger" title="No en planilla"></i>
                                            @endif
                                        </td>
                                        @canany(['gestionar_tesoreria', 'supervisar_tesoreria'])
                                            <td
                                                class="text-center{{ !$arrendamiento->confirmado ? ' table-warning' : '' }} align-middle d-print-none">
                                                <div class="custom-control custom-switch" style="transform: scale(0.8);">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="confirmado-{{ $arrendamiento->id }}"
                                                        wire:click.prevent="toggleConfirmado({{ $arrendamiento->id }})"
                                                        {{ $arrendamiento->confirmado ? 'checked' : '' }}>
                                                    <label class="custom-control-label"
                                                        for="confirmado-{{ $arrendamiento->id }}"></label>
                                                </div>
                                            </td>
                                        @endcanany
                                        <td class="text-center align-middle d-print-none">
                                            <button wire:click="showDetails({{ $arrendamiento->id }})"
                                                class="btn btn-sm btn-info" data-toggle="modal"
                                                data-target="#detailsModal" title="Detalles"><i
                                                    class="fas fa-eye"></i></button>
                                            <button wire:click="editIngreso({{ $arrendamiento->id }})" class="btn btn-sm btn-success" title="Ingreso"><i class="fas fa-file-invoice-dollar"></i></button>
                                            <button wire:click="edit({{ $arrendamiento->id }})"
                                                class="btn btn-sm btn-primary" title="Editar"><i
                                                    class="fas fa-edit"></i></button>
                                            <button
                                                onclick="event.preventDefault(); window.dispatchEvent(new CustomEvent('swal:confirm', { detail: { title: '¿Estás seguro?', text: '¡No podrás revertir esto!', method: 'destroy', id: {{ $arrendamiento->id }}, confirmButtonText: 'Sí, elimínalo' } }))"
                                                class="btn btn-sm btn-danger" title="Eliminar"><i
                                                    class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="@canany(['gestionar_tesoreria', 'supervisar_tesoreria']) 10 @else 9 @endcanany"
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
                                                    {{ number_format($subtotal->total, 2, ',', '.') }}</span></strong>
                                        </td>
                                        <td colspan="@canany(['gestionar_tesoreria', 'supervisar_tesoreria']) 7 @else 6 @endcanany"
                                            class="align-middle"></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3" class="text-right align-middle"><strong>Total General:</strong>
                                    </td>
                                    <td class="text-right align-middle"><strong><span class="text-nowrap-custom">$
                                                {{ number_format($total, 2, ',', '.') }}</span></strong></td>
                                    <td
                                        colspan="@canany(['gestionar_tesoreria', 'supervisar_tesoreria']) 7 @else 6 @endcanany">
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">
                        @if($arrendamientos instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            {{ $arrendamientos->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ingreso Modal -->
    <div wire:ignore.self class="modal fade" id="ingresoModal" tabindex="-1" role="dialog"
        aria-labelledby="ingresoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ingresoModalLabel">Registrar Ingreso</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form>
                        <dl class="row">
                            <dt class="col-sm-3">Nombre</dt>
                            <dd class="col-sm-9">{{ $nombre }}</dd>

                            <dt class="col-sm-3">Monto</dt>
                            <dd class="col-sm-9">{{ $monto }}</dd>

                            <dt class="col-sm-3">O/C</dt>
                            <dd class="col-sm-9">{{ $orden_cobro }}</dd>

                            <dt class="col-sm-3">Recibo</dt>
                            <dd class="col-sm-9">{{ $recibo }}</dd>
                        </dl>
                        <div class="form-group">
                            <label for="ingreso_input">Ingreso</label>
                            <input type="text" wire:model.defer="ingreso" id="ingreso_input"
                                class="form-control form-control-sm">
                            @error('ingreso')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click.prevent="updateIngreso()"
                        class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div wire:ignore.self class="modal fade" id="arrendamientoModal" tabindex="-1" role="dialog"
        aria-labelledby="arrendamientoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="arrendamientoModalLabel">{{ $arrendamiento_id ? 'Editar' : 'Crear' }}
                        Arrendamiento</h5>
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
                                    <input type="date" wire:model="fecha" id="fecha"
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
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" wire:model.defer="nombre" id="nombre"
                                class="form-control form-control-sm">
                            @error('nombre')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cedula">Cédula</label>
                                    <input type="text" wire:model.defer="cedula" id="cedula"
                                        class="form-control form-control-sm">
                                    @error('cedula')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="text" wire:model.defer="telefono" id="telefono"
                                        class="form-control form-control-sm">
                                    @error('telefono')
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
                                         @foreach($mediosDePago as $medio)
                                             <option value="{{ $medio->nombre }}">{{ $medio->nombre }}</option>
                                         @endforeach
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
                    <button type="button" wire:click.prevent="{{ $arrendamiento_id ? 'update()' : 'store()' }}"
                        class="btn btn-primary">{{ $arrendamiento_id ? 'Actualizar' : 'Guardar' }}</button>
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
                    <h5 class="modal-title" id="detailsModalLabel">Detalles del Arrendamiento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        wire:click="resetDetails()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($selectedArrendamiento)
                        <p class="mb-0"><strong>Fecha:</strong> {{ $selectedArrendamiento->fecha->format('d/m/Y') }}
                        </p>
                        <p class="mb-0"><strong>Ingreso:</strong> {{ $selectedArrendamiento->ingreso }}</p>
                        <p class="mb-0"><strong>Nombre:</strong> {{ $selectedArrendamiento->nombre }}</p>
                        <p class="mb-0"><strong>Cédula:</strong> {{ $selectedArrendamiento->cedula }}</p>
                        <p class="mb-0"><strong>Teléfono:</strong> {{ $selectedArrendamiento->telefono }}</p>
                        <p class="mb-0"><strong>Medio de Pago:</strong> {{ $selectedArrendamiento->medio_de_pago }}
                        </p>
                        <p class="mb-0"><strong>Monto:</strong> <span
                                class="text-nowrap-custom">{{ $selectedArrendamiento->monto_formateado }}</span></p>
                        <p class="mb-0"><strong>Detalle:</strong> {{ $selectedArrendamiento->detalle }}</p>
                        <p class="mb-0"><strong>Orden de Cobro:</strong> {{ $selectedArrendamiento->orden_cobro }}
                        </p>
                        <p class="mb-0"><strong>Recibo:</strong> {{ $selectedArrendamiento->recibo }}</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        wire:click="resetDetails()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <livewire:tesoreria.arrendamientos.planillas-manager :mes="$mes" :year="$year" :key="$mes . $year" />

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
                $('#arrendamientoModal').modal('hide');
            });

            window.addEventListener('alert', event => {
                // alert(event.detail.message);
                const type = event.detail.type;
                const message = event.detail.message;
                Swal.fire({
                    icon: type,
                    title: message,
                    showConfirmButton: false,
                    timer: 1500
                });
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

            window.livewire.on('arrendamientoStore', () => {
                $('#arrendamientoModal').modal('hide');
            });

            window.livewire.on('arrendamientoUpdate', () => {
                $('#arrendamientoModal').modal('hide');
                $('#ingresoModal').modal('hide');
            });

            $(document).ready(function() {
                $('#arrendamientoModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });

                $('#ingresoModal').on('hidden.bs.modal', function() {
                    window.livewire.emit('resetForm');
                });

                $('#arrendamientoModal').on('shown.bs.modal', function() {
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

                $('#ingresoModal').on('shown.bs.modal', function() {
                    $('#ingreso_input').focus();
                });
            });
        </script>
    @endpush

</div>
