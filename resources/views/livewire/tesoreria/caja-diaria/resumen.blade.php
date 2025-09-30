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
    <div class="row mt-2">
        {{-- Caja Inicial --}}
        <div class="col-md-6">
            <div class="accordion" id="accordionCajaInicial">
                <div class="card">
                    <div class="card-header p-0" id="headingCajaInicial">
                        <h5 class="mb-0">
                            <button class="btn btn-info btn-block" type="button" data-toggle="collapse" data-target="#collapseCajaInicial" aria-expanded="false" aria-controls="collapseCajaInicial">
                                Caja Inicial
                            </button>
                        </h5>
                    </div>
                    <div id="collapseCajaInicial" class="collapse" aria-labelledby="headingCajaInicial" data-parent="#accordionCajaInicial" wire:ignore.self>
                        <div class="card-body p-2">
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
                                                <input type="number" class="form-control form-control-sm monto-input tab-on-enter" wire:model.debounce.200ms="cajaInicialPorDenominacion.{{ $denominacion->id }}.monto" value="{{ $cajaInicialPorDenominacion[$denominacion->id]['monto'] ?? 0 }}" min="0" step="0.01" onfocus="this.select()">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm cantidad-input tab-on-enter" wire:model.debounce.200ms="cajaInicialPorDenominacion.{{ $denominacion->id }}.cantidad" value="{{ $cajaInicialPorDenominacion[$denominacion->id]['cantidad'] ?? 0 }}" min="0" step="1" onfocus="this.select()">
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
                            <div class="text-right mt-2">
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
                    <div class="card-header p-0" id="headingCierre">
                        <h5 class="mb-0">
                            <button class="btn btn-info btn-block" type="button" data-toggle="collapse" data-target="#collapseCierre" aria-expanded="false" aria-controls="collapseCierre">
                                Cierre de Caja
                            </button>
                        </h5>
                    </div>
                    <div id="collapseCierre" class="collapse" aria-labelledby="headingCierre" data-parent="#accordionCierre" wire:ignore.self>
                        <div class="card-body p-2">
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
                                                <input type="number" class="form-control form-control-sm monto-input tab-on-enter" wire:model.debounce.200ms="cierrePorDenominacion.{{ $denominacion->id }}.monto" value="{{ $cierrePorDenominacion[$denominacion->id]['monto'] ?? 0 }}" min="0" step="0.01" onfocus="this.select()">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm cantidad-input tab-on-enter" wire:model.debounce.200ms="cierrePorDenominacion.{{ $denominacion->id }}.cantidad" value="{{ $cierrePorDenominacion[$denominacion->id]['cantidad'] ?? 0 }}" min="0" step="1" onfocus="this.select()">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-info">
                                            <td colspan="2" class="text-right font-weight-bold">Total Cargado:</td>
                                            <td colspan="2" class="font-weight-bold">$ {{ number_format($cierreTotal, 2, ',', '.') }}</td>
                                        </tr>
                                        @if ($cierreTotal != $saldoActual)
                                        <tr class="table-danger">
                                            <td colspan="2" class="text-right font-weight-bold">Diferencia:</td>
                                            <td colspan="2" class="font-weight-bold">$ {{ number_format($cierreTotal - $saldoActual, 2, ',', '.') }}</td>
                                        </tr>
                                        @endif
                                    </tfoot>
                                </table>
                            </div>
                            <div class="text-right mt-2">
                                <button type="button" class="btn btn-success" wire:click="guardarCierreCaja" @if(!$cajaDiariaExists) disabled @endif>Guardar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>

        // Usar delegación de eventos de jQuery para máxima robustez con Livewire
        $(document).on('shown.bs.collapse', '#accordionCajaInicial, #accordionCierre', function (e) {
            const firstInput = e.target.querySelector('input[type="number"]');
            if (firstInput) {
                setTimeout(() => {
                    firstInput.focus();
                    firstInput.select();
                }, 50); // Pequeño retardo para asegurar compatibilidad
            }
        });

        function handleEnter(e) {
            if (e.key === 'Enter') {
                e.preventDefault();

                const activeElement = document.activeElement;
                const formContainer = activeElement.closest('.collapse');
                if (!formContainer) return;

                let inputs = [];
                if (activeElement.classList.contains('monto-input')) {
                    inputs = Array.from(formContainer.querySelectorAll('.monto-input'));
                } else if (activeElement.classList.contains('cantidad-input')) {
                    inputs = Array.from(formContainer.querySelectorAll('.cantidad-input'));
                } else {
                    // If the active element is the save button, click it
                    if (activeElement.classList.contains('btn-success')) {
                        activeElement.click();
                        return;
                    }
                    return;
                }

                const currentIndex = inputs.indexOf(activeElement);
                if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                } else {
                    const button = formContainer.querySelector('.btn-success');
                    if (button) button.focus();
                }
            }
        }

        document.addEventListener('keydown', handleEnter, true);

        document.addEventListener('livewire:destroy', () => {
            document.removeEventListener('keydown', handleEnter, true);
        });

        document.addEventListener('cajaInicialGuardada', function () {
            const alert = document.querySelector('.alert-warning');
            if (alert) {
                $(alert).alert('close');
            }
        });

        document.addEventListener('cierreCajaGuardado', function () {
            const alert = document.querySelector('.alert-warning');
            if (alert) {
                $(alert).alert('close');
            }
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