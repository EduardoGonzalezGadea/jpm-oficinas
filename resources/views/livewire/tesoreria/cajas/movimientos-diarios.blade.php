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

    {{-- Movimientos del Día --}}
    <div>
        @livewire('tesoreria.cajas.movimientos')
    </div>
</div>
