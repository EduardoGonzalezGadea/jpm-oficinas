<div>
    <div class="card">
        <div class="card-body">
            @php
                $cheques = $cheques ?? [];
            @endphp
            @if(count($cheques) > 0)
                <form wire:submit.prevent="generar">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>Seleccione los cheques que desea incluir en la planilla.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center align-middle" width="50">
                                        <input type="checkbox" wire:click="selectAll" class="form-check-input">
                                    </th>
                                    <th>Número</th>
                                    <th>Banco</th>
                                    <th>Cuenta</th>
                                    <th>Fecha Emisión</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cheques as $cheque)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" wire:model="chequesSeleccionados" value="{{ $cheque['id'] }}" class="form-check-input">
                                        </td>
                                        <td><strong>{{ $cheque['numero_cheque'] }}</strong></td>
                                        <td>{{ $cheque['cuenta_bancaria']['banco']['nombre'] }}</td>
                                        <td>{{ $cheque['cuenta_bancaria']['numero_cuenta'] }}</td>
                                        <td>{{ $cheque['fecha_emision'] ? \Carbon\Carbon::parse($cheque['fecha_emision'])->format('d/m/Y') : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @error('chequesSeleccionados')
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                        </div>
                    @enderror

                    @if(count($chequesSeleccionados) > 0)
                        <div class="alert alert-info mt-3">
                            <h5><i class="fas fa-calculator mr-2"></i>Resumen de Planilla</h5>
                            <p class="mb-1"><strong>Cheques seleccionados:</strong> {{ count($chequesSeleccionados) }}</p>
                            <p class="mb-0"><strong>Monto total:</strong> ${{ number_format($this->totalSeleccionado, 2) }}</p>
                        </div>
                    @endif

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                            <i class="fas fa-save mr-2"></i>
                            <span wire:loading.remove>Generar Planilla</span>
                            <span wire:loading>Generando...</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>No hay cheques emitidos disponibles para generar planilla.
                </div>
            @endif
        </div>
    </div>
</div>
