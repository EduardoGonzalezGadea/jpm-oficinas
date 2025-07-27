<div>
    <!-- Tabla Pendiente Detalle (Sin cambios) -->
    <h4 class="mt-4">Detalle del Pendiente</h4>
    <table class="table table-striped table-bordered" id="tablaCajaChica">
        <thead class="thead-dark">
            <tr>
                <th class="text-center">N°</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Dependencia</th>
                <th class="text-center">Monto</th>
                <th class="text-center">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $pendiente->pendiente }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($pendiente->fechaPendientes)->format('d/m/Y') }}</td>
                <td class="text-center">{{ $pendiente->dependencia->dependencia }}</td>
                <td class="text-center">$ {{ number_format($pendiente->montoPendientes, 2, ',', '.') }}</td>
                <td class="text-center {{ $pendiente->saldo > 0 ? 'text-warning font-weight-bold' : '' }}">
                    $ {{ number_format($pendiente->saldo, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <hr>

    {{-- Formulario para editar pendiente con el nuevo diseño de "Input Group" --}}
    <h4 class="mt-4">Modificar datos del Pendiente</h4>

    <form wire:submit.prevent="guardarCambios">
        <div class="row">
            
            <!-- ====== Columna Izquierda ====== -->
            <div class="col-md-6">
                
                {{-- Grupo de campo para "Número" --}}
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">Número</span>
                        <input type="number" wire:model.live="nroPendiente"
                               class="form-control @error('nroPendiente') is-invalid @enderror"
                               placeholder="Ingrese el número" 
                               value="{{ $pendiente->pendiente }}">
                    </div>
                    @error('nroPendiente')
                        {{-- La clase d-block asegura que el mensaje se muestre bajo el input-group --}}
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Grupo de campo para "Fecha" --}}
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">Fecha</span>
                        <input type="date" wire:model.live="fechaPendientes"
                               class="form-control @error('fechaPendientes') is-invalid @enderror" 
                               placeholder="Seleccione la fecha"
                               value="{{ $pendiente->fechaPendientes->format('Y-m-d') }}">
                    </div>
                    @error('fechaPendientes')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- ====== Columna Derecha ====== -->
            <div class="col-md-6">

                {{-- Grupo de campo para "Dependencia" --}}
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">Dependencia</span>
                        <select wire:model.live="relDependencia"
                                class="form-control @error('relDependencia') is-invalid @enderror" 
                                value="{{ $pendiente->dependencia->idDependencias }}">
                            <option value="">Seleccione una dependencia</option>
                            @foreach ($dependencias as $dependencia)
                                @if ($pendiente->dependencia->idDependencias == $dependencia->idDependencias)
                                    <option value="{{ $dependencia->idDependencias }}" selected>{{ $dependencia->dependencia }}</option>
                                @else
                                    <option value="{{ $dependencia->idDependencias }}">{{ $dependencia->dependencia }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @error('relDependencia')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Grupo de campo para "Monto" --}}
                <div class="mb-3">
                    <div class="input-group">
                        {{-- Usamos el signo '$' para que coincida con tu ejemplo --}}
                        <span class="input-group-text">Monto en $</span>
                        <input type="number" step="1.00" min="0.0" wire:model.live="montoPendientes"
                               class="form-control @error('montoPendientes') is-invalid @enderror"
                               placeholder="Ingrese el monto"
                               value="{{ $pendiente->montoPendientes }}">
                    </div>
                    @error('montoPendientes')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Fila para los botones --}}
        <div class="row mt-3">
            <div class="col-md-12 text-right">
                <button type="reset" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </div>
    </form>
    
    <hr>

    {{-- El componente hijo recibe los datos actualizados automáticamente --}}
    <livewire:tesoreria.caja-chica.pendiente.movimientos-pendiente :movimientos="$pendiente->movimientos" :id="$pendiente->idPendientes" />
</div>