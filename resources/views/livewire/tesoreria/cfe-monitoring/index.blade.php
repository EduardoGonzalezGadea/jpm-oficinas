<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Monitoreo CFE</h4>
        <div class="btn-group btn-group-sm">
            <button wire:click="cambiarPeriodo('24h')" class="btn {{ $periodo === '24h' ? 'btn-primary' : 'btn-outline-primary' }}">24h</button>
            <button wire:click="cambiarPeriodo('7d')" class="btn {{ $periodo === '7d' ? 'btn-primary' : 'btn-outline-primary' }}">7 días</button>
            <button wire:click="cambiarPeriodo('30d')" class="btn {{ $periodo === '30d' ? 'btn-primary' : 'btn-outline-primary' }}">30 días</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body text-center py-3">
                    <h2 class="mb-0">{{ $stats['total'] ?? 0 }}</h2>
                    <small>Recibidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body text-center py-3">
                    <h2 class="mb-0">{{ $stats['confirmados'] ?? 0 }}</h2>
                    <small>Confirmados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark shadow-sm">
                <div class="card-body text-center py-3">
                    <h2 class="mb-0">{{ $stats['pendientes'] ?? 0 }}</h2>
                    <small>Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white shadow-sm">
                <div class="card-body text-center py-3">
                    <h2 class="mb-0">{{ $stats['rechazados'] ?? 0 }}</h2>
                    <small>Rechazados</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Tendencia de carga (últimos 7 días)</strong>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-end justify-content-around" style="height: 120px;">
                        @foreach($tendencia as $fecha => $count)
                        <div class="text-center">
                            <div class="bg-primary rounded mb-1 mx-auto" style="width: 30px; height: {{ max(4, ($count / max(1, max($tendencia))) * 100) }}px; opacity: 0.7;"></div>
                            <small class="text-muted" style="font-size: 10px;">{{ $fecha }}</small>
                            <br><small class="fw-bold">{{ $count }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Por tipo de CFE</strong>
                </div>
                <div class="card-body">
                    @forelse(($stats['por_tipo'] ?? []) as $tipo => $count)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>{{ ucfirst(str_replace('_', ' ', $tipo)) }}</span>
                        <span class="badge badge-primary badge-pill">{{ $count }}</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar" style="width: {{ $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0 }}%"></div>
                    </div>
                    @empty
                    <p class="text-muted mb-0">Sin datos en este período</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header">
            <strong>Tasa de éxito</strong>
        </div>
        <div class="card-body text-center">
            <h1 class="display-4 {{ ($stats['tasa_exito'] ?? 0) >= 80 ? 'text-success' : 'text-danger' }}">
                {{ $stats['tasa_exito'] ?? 0 }}%
            </h1>
            <p class="text-muted">
                {{ $stats['confirmados'] ?? 0 }} confirmados de {{ $stats['total'] ?? 0 }} recibidos
            </p>
        </div>
    </div>

    <div class="text-right">
        <a href="{{ route('tesoreria.cfe.pendientes') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-list mr-1"></i>Ir a Pendientes
        </a>
    </div>
</div>
