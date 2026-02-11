<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-print mr-2"></i>{{ $title ?? 'Generar Reporte Avanzado' }}</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-info" role="alert">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Instrucciones:</strong> Seleccione los criterios de búsqueda deseados. El reporte se generará únicamente si especifica al menos un filtro.
                </div>

                <form wire:submit.prevent="validateAndGenerate">
                    {{ $slot }}

                    <div class="row mt-4">
                        <div class="col-md-12 text-right">
                            <button type="button" wire:click="resetFilters" class="btn btn-secondary mr-2">
                                <i class="fas fa-eraser mr-1"></i> Limpiar Filtros
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>