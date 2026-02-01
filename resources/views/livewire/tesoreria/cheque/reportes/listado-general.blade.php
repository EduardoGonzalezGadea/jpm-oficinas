@if($chequesFiltrados->count() > 0)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center d-print-none">
        <h5 class="card-title mb-0">
            <i class="fas fa-list mr-2"></i>Listado General de Cheques
        </h5>
        <div class="d-flex align-items-center">
            <span class="badge badge-info mr-2">
                @if ($chequesFiltrados instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $chequesFiltrados->total() }}
                @else
                {{ $chequesFiltrados->count() }}
                @endif
                registros
            </span>
            <select wire:model="perPage" class="form-control form-control-sm d-print-none" style="width: auto;">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="thead-dark">
                    <tr class="text-center">
                        <th class="align-middle">Serie</th>
                        <th class="align-middle">N° Cheque</th>
                        <th class="align-middle">Banco</th>
                        <th class="align-middle">Cuenta</th>
                        <th class="align-middle">Estado</th>
                        <th class="align-middle">Fecha Ingreso</th>
                        <th class="align-middle">Fecha Emisión</th>
                        <th class="align-middle">Fecha Anulación</th>
                        <th class="align-middle">Motivo Anulación</th>
                        <th class="align-middle">En Planilla</th>
                        <th class="align-middle">Monto</th>
                        <th class="align-middle">Beneficiario</th>
                        <th class="align-middle">Concepto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chequesFiltrados as $cheque)
                    <tr class="text-center">
                        <td class="align-middle">{{ $cheque->serie }}</td>
                        <td class="align-middle">{{ $cheque->numero_cheque }}</td>
                        <td class="align-middle">{{ $cheque->cuentaBancaria->banco->codigo }}</td>
                        <td class="align-middle">{{ $cheque->cuentaBancaria->numero_cuenta }}</td>
                        <td class="align-middle">
                            @switch($cheque->estado)
                            @case('disponible')
                            <span class="badge badge-success">Disponible</span>
                            @break
                            @case('emitido')
                            <span class="badge badge-primary">Emitido</span>
                            @break
                            @case('anulado')
                            <span class="badge badge-danger">Anulado</span>
                            @break
                            @case('en_planilla')
                            <span class="badge badge-info">En Planilla</span>
                            @break
                            @default
                            <span class="badge badge-secondary">{{ ucfirst($cheque->estado) }}</span>
                            @endswitch
                        </td>
                        <td class="align-middle">
                            {{ $cheque->created_at ? $cheque->created_at->format('d/m/Y') : '-' }}
                        </td>
                        <td class="align-middle">
                            {{ $cheque->fecha_emision ? $cheque->fecha_emision->format('d/m/Y') : '-' }}
                        </td>
                        <td class="align-middle">
                            {{ $cheque->fecha_anulacion ? $cheque->fecha_anulacion->format('d/m/Y') : '-' }}
                        </td>
                        <td class="align-middle">
                            {{ $cheque->motivo_anulacion ?? '-' }}
                        </td>
                        <td class="align-middle">
                            @if($cheque->planilla_id)
                            <span class="badge badge-warning">
                                <i class="fas fa-file-alt mr-1"></i>{{ $cheque->planilla->numero_planilla ?? 'N/A' }}
                            </span>
                            @else
                            <span class="badge badge-light">No</span>
                            @endif
                        </td>
                        <td class="align-middle">
                            @if($cheque->monto)
                            ${{ number_format($cheque->monto, 2, ',', '.') }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="align-middle">
                            @if($cheque->beneficiario)
                            <small>{{ $cheque->beneficiario }}</small>
                            @else
                            -
                            @endif
                        </td>
                        <td class="align-middle">
                            @if($cheque->concepto)
                            <small>{{ $cheque->concepto }}</small>
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if ($chequesFiltrados instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="d-flex justify-content-center mt-4 d-print-none">
            {{ $chequesFiltrados->links() }}
        </div>
        @endif
    </div>
</div>
@else
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>No se encontraron cheques que coincidan con los filtros aplicados.
</div>
@endif
