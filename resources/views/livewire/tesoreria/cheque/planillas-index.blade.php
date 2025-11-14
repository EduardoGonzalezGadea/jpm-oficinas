<div>
    <div class="card">
        <div class="card-header py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fas fa-list mr-2"></i>Planillas de Cheques
                </h4>
                <div class="form-inline">
                    <label for="añoSeleccionado" class="mr-2">Año:</label>
                    <select wire:model="añoSeleccionado" class="form-control form-control-sm">
                        @foreach($this->getAñosDisponibles() as $año)
                            <option value="{{ $año }}">{{ $año }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-2">
            @if($planillas && count($planillas) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="thead-dark">
                            <tr style="font-size: 0.8125rem;">
                                <th class="py-1">Número de Planilla</th>
                                <th class="py-1">Fecha Generación</th>
                                <th class="py-1">Estado</th>
                                <th class="py-1 text-center">Cantidad Cheques</th>
                                <th class="py-1">Acciones</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.875rem;">
                            @foreach($planillas as $planilla)
                                <tr style="font-size: 0.875rem;">
                                    <td class="py-1">{{ $planilla['numero_planilla'] }}</td>
                                    <td class="py-1">{{ \Carbon\Carbon::parse($planilla['fecha_generacion'])->format('d/m/Y H:i') }}</td>
                                    <td class="py-1">
                                        <span class="badge badge-sm badge-{{ $planilla['estado'] === 'generada' ? 'success' : 'danger' }}">
                                            {{ ucfirst($planilla['estado']) }}
                                        </span>
                                    </td>
                                    <td class="py-1 text-center">{{ count($planilla['cheques']) }}</td>
                                    <td class="py-1">
                                        <a href="{{ route('tesoreria.cheques.planilla.ver', $planilla['id']) }}" class="btn btn-sm btn-primary btn-xs py-0 px-1" title="Ver Planilla">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('tesoreria.cheques.planilla.imprimir', $planilla['id']) }}" target="_blank" class="btn btn-sm btn-info btn-xs ml-1 py-0 px-1" title="Imprimir Planilla">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>No hay planillas de cheques registradas.
                </div>
            @endif
        </div>
    </div>
</div>
