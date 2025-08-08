<div>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Gestión de Denominaciones</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="guardar">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Valor</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" wire:model="valor" class="form-control" step="0.01"
                                    min="0.01">
                            </div>
                            @error('valor')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipo</label>
                            <select wire:model="tipo" class="form-control">
                                <option value="BILLETE">Billete</option>
                                <option value="MONEDA">Moneda</option>
                            </select>
                            @error('tipo')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" wire:model="orden" class="form-control" min="0">
                            @error('orden')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Estado</label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" wire:model="activo" class="custom-control-input"
                                    id="activoSwitch">
                                <label class="custom-control-label" for="activoSwitch">
                                    {{ $activo ? 'Activo' : 'Inactivo' }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">
                            {{ $modo === 'crear' ? 'Crear' : 'Actualizar' }} Denominación
                        </button>
                        @if ($modo === 'editar')
                            <button type="button" wire:click="resetForm" class="btn btn-secondary">
                                Cancelar
                            </button>
                        @endif
                    </div>
                </div>
            </form>

            <hr>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h6>Billetes</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Valor</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($billetes as $billete)
                                    <tr>
                                        <td>${{ number_format($billete->valor, 2) }}</td>
                                        <td>{{ $billete->orden }}</td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="billete{{ $billete->idDenominacion }}"
                                                    wire:click="toggleEstado({{ $billete->idDenominacion }})"
                                                    {{ $billete->activo ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="billete{{ $billete->idDenominacion }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <button wire:click="editar({{ $billete->idDenominacion }})"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6>Monedas</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Valor</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monedas as $moneda)
                                    <tr>
                                        <td>${{ number_format($moneda->valor, 2) }}</td>
                                        <td>{{ $moneda->orden }}</td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="moneda{{ $moneda->idDenominacion }}"
                                                    wire:click="toggleEstado({{ $moneda->idDenominacion }})"
                                                    {{ $moneda->activo ? 'checked' : '' }}>
                                                <label class="custom-control-label"
                                                    for="moneda{{ $moneda->idDenominacion }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <button wire:click="editar({{ $moneda->idDenominacion }})"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mt-3">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif
</div>
