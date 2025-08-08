<div>
    {{-- Tarjetas de Resumen --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Saldo Inicial</h5>
                    <p class="card-text">$ 1,250.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Total Entradas</h5>
                    <p class="card-text">$ 500.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">Total Salidas</h5>
                    <p class="card-text">$ 250.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Saldo Actual</h5>
                    <p class="card-text">$ 1,500.00</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario de Registro de Movimiento --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">Registrar Nuevo Movimiento</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="registrarMovimiento">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="tipo">Tipo de Movimiento</label>
                        <select id="tipo" class="form-control" wire:model="tipo">
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="concepto">Concepto</label>
                        <input type="text" id="concepto" class="form-control" wire:model="concepto" placeholder="Ej: Venta de producto">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="monto">Monto</label>
                        <input type="number" id="monto" class="form-control" wire:model="monto" placeholder="0.00">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Registrar Movimiento</button>
            </form>
        </div>
    </div>

    {{-- Tabla de Movimientos del Día --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Movimientos del Día</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>Concepto</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>09:15 AM</td>
                        <td><span class="badge badge-success">Entrada</span></td>
                        <td>Venta de producto A</td>
                        <td>$ 200.00</td>
                    </tr>
                    <tr>
                        <td>10:30 AM</td>
                        <td><span class="badge badge-danger">Salida</span></td>
                        <td>Compra de insumos</td>
                        <td>$ 50.00</td>
                    </tr>
                    <tr>
                        <td>11:00 AM</td>
                        <td><span class="badge badge-success">Entrada</span></td>
                        <td>Venta de producto B</td>
                        <td>$ 300.00</td>
                    </tr>
                    <tr>
                        <td>01:45 PM</td>
                        <td><span class="badge badge-danger">Salida</span></td>
                        <td>Pago de servicio</td>
                        <td>$ 200.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
