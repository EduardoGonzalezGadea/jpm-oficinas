@if ($stockCheques->count() > 0)
    @foreach($stockCheques as $stock)
        <div class="card mb-3">
            <div class="card-header bg-info text-white py-2">
                <h5 class="card-title mb-0">
                    <i class="fas fa-university mr-2"></i>
                    Cuenta: {{ $stock['cuenta']->banco->codigo }} - {{ $stock['cuenta']->numero_cuenta }}
                </h5>
            </div>
            <div class="card-body py-2 px-3">
                @foreach($stock['series'] as $dataSerie)
                    <div class="card mb-3" style="border: 3px solid #dee2e6 !important; border-radius: 0.5rem;">
                        <div class="card-header py-1" style="background-color: #f0f0f0;">
                            <h6 class="card-title mb-0 font-weight-bold">Serie: {{ $dataSerie['serie'] }}</h6>
                        </div>
                        <div class="card-body py-2 px-2">
                            <div class="row">
                                <!-- Libretas Completas -->
                                @if ($dataSerie['completas']->count() > 0)
                                    <div class="col-12 mb-2">
                                        <h6 class="text-success"><i class="fas fa-check-circle mr-2"></i>Libretas Completas</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="thead-light">
                                                    <tr class="text-center">
                                                        <th class="align-middle py-1">Tanda</th>
                                                        <th class="align-middle py-1">Desde</th>
                                                        <th class="align-middle py-1">Hasta</th>
                                                        <th class="align-middle py-1">Total Cheques</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($dataSerie['completas'] as $libreta)
                                                        <tr class="text-center">
                                                            <td class="align-middle py-1">{{ $libreta['tanda'] }}</td>
                                                            <td class="align-middle py-1">{{ $libreta['numero_inicial'] }}</td>
                                                            <td class="align-middle py-1">{{ $libreta['numero_final'] }}</td>
                                                            <td class="align-middle py-1"><span class="font-weight-bold">{{ $libreta['total_cheques'] }}</span></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                <!-- Libretas Incompletas -->
                                @if ($dataSerie['incompletas']->count() > 0)
                                    <div class="col-12">
                                        <h6 class="text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Libretas Incompletas</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="thead-light">
                                                    <tr class="text-center">
                                                        <th class="align-middle py-1">Libreta</th>
                                                        <th class="align-middle py-1">Primer Disp.</th>
                                                        <th class="align-middle py-1">Último Disp.</th>
                                                        <th class="align-middle py-1">Usados</th>
                                                        <th class="align-middle py-1">Anulados</th>
                                                        <th class="align-middle py-1">Disponibles</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($dataSerie['incompletas'] as $libreta)
                                                        <tr class="text-center">
                                                            <td class="align-middle py-1">{{ $libreta['numero_inicial_libreta'] }} - {{ $libreta['numero_final_libreta'] }}</td>
                                                            <td class="align-middle py-1">{{ $libreta['primer_cheque_disponible'] }}</td>
                                                            <td class="align-middle py-1">{{ $libreta['ultimo_cheque_disponible'] }}</td>
                                                            <td class="align-middle py-1"><span class="font-weight-bold">{{ $libreta['cheques_usados'] }}</span></td>
                                                            <td class="align-middle py-1"><span class="font-weight-bold">{{ $libreta['cheques_anulados'] }}</span></td>
                                                            <td class="align-middle py-1"><span class="font-weight-bold">{{ $libreta['cheques_disponibles'] }}</span></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- Resumen General -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card print-avoid-break" style="border: 3px solid #dee2e6 !important; border-radius: 0.5rem;">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0"><i class="fas fa-chart-pie mr-2"></i>Resumen General Total</h6>
                </div>
                <div class="card-body py-2 px-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border p-2 rounded h-100">
                                <h5 class="font-weight-bold">{{ $stockCheques->sum(function($s) { return $s['series']->sum(function($serie) { return $serie['completas']->sum('total_cheques'); }); }) }}</h5>
                                <p class="mb-0 small">Cheques en libretas completas</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border p-2 rounded h-100">
                                <h5 class="font-weight-bold">{{ $stockCheques->sum(function($s) { return $s['series']->sum(function($serie) { return $serie['incompletas']->sum('cheques_disponibles'); }); }) }}</h5>
                                <p class="mb-0 small">Cheques disponibles en libretas incompletas</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border p-2 rounded h-100">
                                <h5 class="font-weight-bold">{{ $stockCheques->sum(function($s) { return $s['series']->sum(function($serie) { return $serie['completas']->sum('total_cheques'); }); }) + $stockCheques->sum(function($s) { return $s['series']->sum(function($serie) { return $serie['incompletas']->sum('cheques_disponibles'); }); }) }}</h5>
                                <p class="mb-0 small">Total de cheques disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-2"></i>No hay cheques disponibles en stock.
    </div>
@endif
