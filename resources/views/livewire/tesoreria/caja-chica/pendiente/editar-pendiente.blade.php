<div>
    <!-- Tabla Pendiente Detalle con botón de editar -->
    <h4 class="mt-4">Detalle del Pendiente</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered" id="tablaCajaChica">
            <thead class="thead-dark">
                <tr>
                    <th class="text-center">N°</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-center">Dependencia</th>
                    <th class="text-center">Monto</th>
                    <th class="text-center">Total Rendido</th>
                    <th class="text-center">Total Recuperado</th>
                    <th class="text-center">Saldo</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">{{ $pendiente->pendiente }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($pendiente->fechaPendientes)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $pendiente->dependencia->dependencia }}</td>
                    <td class="text-center text-nowrap">$ {{ number_format($pendiente->montoPendientes, 2, ',', '.') }}</td>
                    <td class="text-center text-nowrap">$ {{ number_format($pendiente->movimientos->sum('rendido'), 2, ',', '.') }}</td>
                    <td class="text-center text-nowrap">$ {{ number_format($pendiente->movimientos->sum('recuperado'), 2, ',', '.') }}</td>
                    <td class="text-center font-weight-bold text-nowrap">
                        $ {{ number_format($pendiente->saldo, 2, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary" title="Editar" data-toggle="modal" data-target="#modalEditarDetalle">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- El componente hijo para el modal de editar --}}
    <livewire:tesoreria.caja-chica.pendiente.modal-editar-detalle :id="$pendiente->idPendientes" />

    {{-- El componente hijo recibe los datos actualizados automáticamente --}}
    <livewire:tesoreria.caja-chica.pendiente.movimientos-pendiente :movimientos="$pendiente->movimientos" :id="$pendiente->idPendientes" />
</div>
