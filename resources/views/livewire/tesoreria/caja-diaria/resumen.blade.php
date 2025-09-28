<div>
    {{-- Tarjetas de Resumen --}}
    <div class="d-flex mb-3">
        <div class="px-2 flex-fill">
            <div class="card shadow-sm" style="background-color: #e3f2fd;">
                <div class="card-body p-2">
                    <div class="d-flex flex-column">
                        <h6 class="card-title mb-1 text-primary small text-uppercase">Saldo Inicial</h6>
                        <p class="card-text h4 mb-0 text-dark font-weight-bold">$ {{ number_format($saldoInicial ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-2 flex-fill">
            <div class="card shadow-sm" style="background-color: #e8f5e9;">
                <div class="card-body p-2">
                    <div class="d-flex flex-column">
                        <h6 class="card-title mb-1 text-success small text-uppercase">Total Cobros</h6>
                        <p class="card-text h4 mb-0 text-dark font-weight-bold">$ {{ number_format($totalCobros ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-2 flex-fill">
            <div class="card shadow-sm" style="background-color: #ffebee;">
                <div class="card-body p-2">
                    <div class="d-flex flex-column">
                        <h6 class="card-title mb-1 text-danger small text-uppercase">Total Pagos</h6>
                        <p class="card-text h4 mb-0 text-dark font-weight-bold">$ {{ number_format($totalPagos ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="px-2 flex-fill">
            <div class="card shadow-sm" style="background-color: #e1f5fe;">
                <div class="card-body p-2">
                    <div class="d-flex flex-column">
                        <h6 class="card-title mb-1 text-info small text-uppercase">Saldo Actual</h6>
                        <p class="card-text h4 mb-0 text-dark font-weight-bold">$ {{ number_format($saldoActual ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$cajaDiariaExists)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Advertencia:</strong> No existe una caja diaria para la fecha seleccionada.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    {{-- Cajas Inicial y Cierre --}}
    <div class="row mt-4">
        {{-- Caja Inicial --}}
        <div class="col-md-6">
            <div class="accordion" id="accordionCajaInicial">
                <div class="card">
                    <div class="card-header" id="headingCajaInicial">
                        <h5 class="mb-0">
                            <button class="btn btn-primary btn-block" type="button" data-toggle="collapse" data-target="#collapseCajaInicial" aria-expanded="false" aria-controls="collapseCajaInicial">
                                Caja Inicial
                            </button>
                        </h5>
                    </div>
                    <div id="collapseCajaInicial" class="collapse" aria-labelledby="headingCajaInicial" data-parent="#accordionCajaInicial" wire:ignore.self>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="tabCajaInicial" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="por-denominacion-inicial-tab" data-toggle="tab" href="#por-denominacion-inicial" role="tab" aria-controls="por-denominacion-inicial" aria-selected="true">Por denominación</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="por-monto-total-inicial-tab" data-toggle="tab" href="#por-monto-total-inicial" role="tab" aria-controls="por-monto-total-inicial" aria-selected="false">Por monto total</a>
                                </li>
                            </ul>
                            <div class="tab-content mt-3" id="tabContentCajaInicial">
                                <div class="tab-pane fade show active" id="por-denominacion-inicial" role="tabpanel" aria-labelledby="por-denominacion-inicial-tab">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tipo</th>
                                                    <th>Denominación</th>
                                                    <th>Monto</th>
                                                    <th>Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($denominaciones as $denominacion)
                                                <tr>
                                                    <td>{{ $denominacion->tipo_moneda }}</td>
                                                    <td>{{ $denominacion->denominacion_formateada }}</td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm monto-input tab-on-enter" wire:model="cajaInicialPorDenominacion.{{ $denominacion->id }}.monto" value="{{ $cajaInicialPorDenominacion[$denominacion->id]['monto'] ?? 0 }}" min="0" step="0.01" onfocus="this.select()" @if($loop->first) autofocus @endif>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm cantidad-input tab-on-enter" wire:model="cajaInicialPorDenominacion.{{ $denominacion->id }}.cantidad" value="{{ $cajaInicialPorDenominacion[$denominacion->id]['cantidad'] ?? 0 }}" min="0" step="1" onfocus="this.select()">
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="2" class="text-right font-weight-bold">Total Cargado:</td>
                                                    <td colspan="2" class="font-weight-bold">$ {{ number_format($cajaInicialTotal, 2, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="por-monto-total-inicial" role="tabpanel" aria-labelledby="por-monto-total-inicial-tab">
                                    <div class="form-group">
                                        <label for="cajaInicialTotal">Monto Total</label>
                                        <input type="number" class="form-control" id="cajaInicialTotal" wire:model="cajaInicialTotal" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="text-right mt-3">
                                <button type="button" class="btn btn-success" wire:click="guardarCajaInicial">Guardar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cierre de Caja --}}
        <div class="col-md-6">
            <div class="accordion" id="accordionCierre">
                <div class="card">
                    <div class="card-header" id="headingCierre">
                        <h5 class="mb-0">
                            <button class="btn btn-primary btn-block" type="button" data-toggle="collapse" data-target="#collapseCierre" aria-expanded="false" aria-controls="collapseCierre">
                                Cierre de Caja
                            </button>
                        </h5>
                    </div>
                    <div id="collapseCierre" class="collapse" aria-labelledby="headingCierre" data-parent="#accordionCierre" wire:ignore.self>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="tabCierre" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="por-denominacion-cierre-tab" data-toggle="tab" href="#por-denominacion-cierre" role="tab" aria-controls="por-denominacion-cierre" aria-selected="true">Por denominación</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="por-monto-total-cierre-tab" data-toggle="tab" href="#por-monto-total-cierre" role="tab" aria-controls="por-monto-total-cierre" aria-selected="false">Por monto total</a>
                                </li>
                            </ul>
                            <div class="tab-content mt-3" id="tabContentCierre">
                                <div class="tab-pane fade show active" id="por-denominacion-cierre" role="tabpanel" aria-labelledby="por-denominacion-cierre-tab">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Tipo</th>
                                                    <th>Denominación</th>
                                                    <th>Monto</th>
                                                    <th>Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($denominaciones as $denominacion)
                                                <tr>
                                                    <td>{{ $denominacion->tipo_moneda }}</td>
                                                    <td>{{ $denominacion->denominacion_formateada }}</td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm monto-input tab-on-enter" wire:model="cierrePorDenominacion.{{ $denominacion->id }}.monto" value="{{ $cierrePorDenominacion[$denominacion->id]['monto'] ?? 0 }}" min="0" step="0.01" onfocus="this.select()" @if($loop->first) autofocus @endif>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm cantidad-input tab-on-enter" wire:model="cierrePorDenominacion.{{ $denominacion->id }}.cantidad" value="{{ $cierrePorDenominacion[$denominacion->id]['cantidad'] ?? 0 }}" min="0" step="1" onfocus="this.select()">
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <td colspan="2" class="text-right font-weight-bold">Total Cargado:</td>
                                                    <td colspan="2" class="font-weight-bold">$ {{ number_format($cierreTotal, 2, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="por-monto-total-cierre" role="tabpanel" aria-labelledby="por-monto-total-cierre-tab">
                                    <div class="form-group">
                                        <label for="cierreTotal">Monto Total</label>
                                        <input type="number" class="form-control" id="cierreTotal" wire:model="cierreTotal" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="text-right mt-3">
                                <button type="button" class="btn btn-success" wire:click="guardarCierreCaja">Guardar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function handleEnter(e) {
            if (e.key === 'Enter') {
                e.preventDefault();

                const activeElement = document.activeElement;

                // Encuentra el contenedor del formulario actual (el acordeón)
                const formContainer = activeElement.closest('.collapse');
                if (!formContainer) return;

                let inputs = [];
                let currentIndex = -1;

                // Determina si estamos en un campo de monto o cantidad
                if (activeElement.classList.contains('monto-input')) {
                    inputs = Array.from(formContainer.querySelectorAll('.monto-input'));
                } else if (activeElement.classList.contains('cantidad-input')) {
                    inputs = Array.from(formContainer.querySelectorAll('.cantidad-input'));
                } else {
                    return; // No es un campo que nos interese
                }

                currentIndex = inputs.indexOf(activeElement);

                if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                    // Si hay un siguiente campo en la lista, enfócalo
                    inputs[currentIndex + 1].focus();
                } else {
                    // Si es el último campo, enfoca el botón de guardar de ese formulario
                    const button = formContainer.querySelector('.btn-success');
                    if (button) button.focus();
                }
            }
        }

        document.addEventListener('keydown', handleEnter, true);

        // Limpia el listener cuando el componente de Livewire se destruye para evitar duplicados
        document.addEventListener('livewire:destroy', () => {
            document.removeEventListener('keydown', handleEnter, true);
        });

        document.addEventListener('shown.bs.collapse', function(e) {
            const collapse = e.target;
            const firstInput = collapse.querySelector('input[type="number"]');
            if (firstInput) firstInput.focus();
        });

        document.addEventListener('shown.bs.tab', function(e) {
            const tab = e.target;
            const targetId = tab.getAttribute('href');
            const target = document.querySelector(targetId);
            const firstInput = target.querySelector('input[type="number"]');
            if (firstInput) firstInput.focus();
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-success')) {
                const cardBody = e.target.closest('.card-body');
                const inputs = cardBody.querySelectorAll('input[type="number"]');
                inputs.forEach(input => {
                    if (input.value === '') {
                        input.value = '0';
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }
        });
    </script>
    @endpush
</div>
