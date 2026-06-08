<div>
    {{-- Panel de Control de Caja Diaria --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-info text-white card-header-gradient py-2 px-3 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><strong><i class="fas fa-cash-register mr-2"></i>Caja Diaria</strong></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <a href="{{ route('tesoreria.caja-diaria.apertura') }}" class="card text-center py-4 text-decoration-none border-success h-100">
                        <div class="card-body">
                            <i class="fas fa-door-open fa-2x text-success mb-2"></i>
                            <h6 class="font-weight-bold">Apertura</h6>
                            <small class="text-muted">Abrir caja del día</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="{{ route('tesoreria.caja-diaria.movimientos') }}" class="card text-center py-4 text-decoration-none border-primary h-100">
                        <div class="card-body">
                            <i class="fas fa-exchange-alt fa-2x text-primary mb-2"></i>
                            <h6 class="font-weight-bold">Movimientos</h6>
                            <small class="text-muted">Registrar ingresos y egresos</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="{{ route('tesoreria.caja-diaria.cierre') }}" class="card text-center py-4 text-decoration-none border-danger h-100">
                        <div class="card-body">
                            <i class="fas fa-lock fa-2x text-danger mb-2"></i>
                            <h6 class="font-weight-bold">Arqueo / Cierre</h6>
                            <small class="text-muted">Conteo y cierre de caja</small>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="{{ route('tesoreria.caja-diaria.historial') }}" class="card text-center py-4 text-decoration-none border-secondary h-100">
                        <div class="card-body">
                            <i class="fas fa-history fa-2x text-secondary mb-2"></i>
                            <h6 class="font-weight-bold">Historial</h6>
                            <small class="text-muted">Consultar cajas anteriores</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="{{ route('tesoreria.caja-diaria.er') }}" class="card text-center py-4 text-decoration-none border-info h-100">
                        <div class="card-body">
                            <i class="fas fa-file-invoice-dollar fa-2x text-info mb-2"></i>
                            <h6 class="font-weight-bold">Estado de Recaudación</h6>
                            <small class="text-muted">Generar ER y distribución contable</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>