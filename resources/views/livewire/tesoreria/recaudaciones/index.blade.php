<div class="container-fluid px-0">
    @section('title', 'Recaudaciones')

    <div class="card">
        <div class="card-header bg-info text-white card-header-gradient p-2">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title px-1 m-0">
                    <strong><i class="fas fa-hand-holding-usd mr-2"></i>Recaudaciones</strong>
                </h4>
                <a href="{{ route('tesoreria.gestion-cfe.index') }}" class="btn btn-light mb-0">
                    <i class="fas fa-arrow-left mr-1"></i> Volver a Gestión de CFEs
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3 align-items-end justify-content-between">
                <div class="col-auto">
                    <label class="form-label small mb-1">Fecha</label>
                    <input type="date" class="form-control form-control-sm" wire:model="fecha" wire:change="$refresh">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm" onclick="imprimirRecaudaciones()">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </button>
                </div>
            </div>

            @php
                $tabsConDatos = collect($grupos)->filter(fn($g) => $g['total_efectivo'] + $g['total_cheque'] + $g['total_transferencia'] + $g['total_pos'] > 0);
            @endphp

            @if($tabsConDatos->isNotEmpty())
                @php $primerActivo = true; @endphp
                <ul class="nav nav-tabs" id="recaudacionesTab" role="tablist">
                    @foreach($grupos as $key => $grupo)
                        @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
                            <li class="nav-item">
                                <a class="nav-link small {{ $primerActivo ? 'active' : '' }}"
                                    id="tab-{{ \Illuminate\Support\Str::slug($key) }}" data-toggle="tab"
                                    href="#content-{{ \Illuminate\Support\Str::slug($key) }}"
                                    role="tab" aria-controls="content-{{ \Illuminate\Support\Str::slug($key) }}"
                                    aria-selected="{{ $primerActivo ? 'true' : 'false' }}">
                                    {{ $grupo['label'] }}
                                </a>
                            </li>
                            @php $primerActivo = false; @endphp
                        @endif
                    @endforeach
                </ul>
                @php $primerActivo = true; @endphp
                <div class="tab-content border border-top-0 p-3" id="recaudacionesTabContent">
                    @foreach($grupos as $key => $grupo)
                        @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
                            <div class="tab-pane fade {{ $primerActivo ? 'show active' : '' }}"
                                id="content-{{ \Illuminate\Support\Str::slug($key) }}" role="tabpanel"
                                aria-labelledby="tab-{{ \Illuminate\Support\Str::slug($key) }}">
                                @php $primerActivo = false; @endphp

                                @foreach($grupo['distribuciones'] as $distKey => $distribucion)
                                    @if(!empty($distribucion['items']))
                                        <div class="card mb-3">
                                            <div class="card-header py-1 px-2 bg-light text-center">
                                                <strong>{{ $distribucion['concepto'] }}</strong>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0 border-top-0">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th class="text-nowrap align-middle">Recibo</th>
                                                                <th class="text-right text-nowrap align-middle">Efectivo</th>
                                                                <th class="text-right text-nowrap align-middle">Cheque</th>
                                                                <th class="text-right text-nowrap align-middle">Transferencia</th>
                                                                <th class="text-right text-nowrap align-middle">POS</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($distribucion['items'] as $rowData)
                                                                <tr>
                                                                    <td class="align-middle small text-nowrap">
                                                                        {{ $rowData['cfe']->documento_tipo }} {{ $rowData['cfe']->documento_serie }}-{{ $rowData['cfe']->documento_numero }}
                                                                    </td>
                                                                    <td class="align-middle small text-right text-nowrap">
                                                                        $ {{ number_format($rowData['efectivo'], 2, ',', '.') }}
                                                                    </td>
                                                                    <td class="align-middle small text-right text-nowrap">
                                                                        $ {{ number_format($rowData['cheque'], 2, ',', '.') }}
                                                                    </td>
                                                                    <td class="align-middle small text-right text-nowrap">
                                                                        $ {{ number_format($rowData['transferencia'], 2, ',', '.') }}
                                                                    </td>
                                                                    <td class="align-middle small text-right text-nowrap">
                                                                        $ {{ number_format($rowData['pos'], 2, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot class="table-active">
                                                            <tr>
                                                                <td class="text-right font-weight-bold small align-middle">Subtotal {{ $distribucion['concepto'] }}:</td>
                                                                <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_efectivo'], 2, ',', '.') }}</td>
                                                                <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_cheque'], 2, ',', '.') }}</td>
                                                                <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_transferencia'], 2, ',', '.') }}</td>
                                                                <td class="text-right font-weight-bold small text-nowrap align-middle">$ {{ number_format($distribucion['total_pos'], 2, ',', '.') }}</td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                                @php
                                    $totalGrupo = $grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'];
                                @endphp
                                <div class="d-flex justify-content-end py-2 px-3 bg-light border rounded">
                                    <table class="table table-sm table-borderless mb-0 text-right" style="width: auto;">
                                        <thead>
                                            <tr>
                                                <th class="text-center align-middle small font-weight-bold">TOTALES GENERALES</th>
                                                <th class="small font-weight-bold text-nowrap px-2">Efectivo</th>
                                                <th class="small font-weight-bold text-nowrap px-2">Cheque</th>
                                                <th class="small font-weight-bold text-nowrap px-2">Transferencia</th>
                                                <th class="small font-weight-bold text-nowrap px-2">POS</th>
                                                <th class="small font-weight-bold text-nowrap pl-3 border-left">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="font-weight-bold align-middle pr-3">TOTAL {{ mb_strtoupper($grupo['label']) }}:</td>
                                                <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_efectivo'], 2, ',', '.') }}</td>
                                                <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_cheque'], 2, ',', '.') }}</td>
                                                <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_transferencia'], 2, ',', '.') }}</td>
                                                <td class="font-weight-bold small text-nowrap align-middle px-2">$ {{ number_format($grupo['total_pos'], 2, ',', '.') }}</td>
                                                <td class="font-weight-bold small text-nowrap align-middle pl-3 border-left">$ {{ number_format($totalGrupo, 2, ',', '.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-muted text-center py-4">No hay recaudaciones para la fecha seleccionada.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function imprimirRecaudaciones() {
            var tabActivo = document.querySelector('#recaudacionesTab .nav-link.active');
            if (!tabActivo) return;

            var targetId = tabActivo.getAttribute('href');
            var contenido = document.querySelector(targetId).innerHTML;

            var ventana = window.open('', '_blank', 'width=800,height=600');
            ventana.document.write('<!DOCTYPE html><html><head><title>Recaudaciones</title>');
            ventana.document.write('<link rel="stylesheet" href="{{ asset('css/app.css') }}">');
            ventana.document.write('<style>');
            ventana.document.write('body{padding:20px;font-family:inherit}table{width:100%}');
            ventana.document.write('.d-print-none,.nav-tabs,.tab-pane.fade{display:none!important}');
            ventana.document.write('.tab-pane.fade.show.active{display:block!important}');
            ventana.document.write('.card{margin-bottom:1rem;border:1px solid #dee2e6;page-break-inside:avoid}');
            ventana.document.write('.card-header{padding:.5rem;background:#f8f9fa;font-weight:700}');
            ventana.document.write('.table{width:100%;border-collapse:collapse}');
            ventana.document.write('.table td,.table th{border:1px solid #dee2e6;padding:.25rem}');
            ventana.document.write('.text-right{text-align:right}.text-nowrap{white-space:nowrap}');
            ventana.document.write('.d-flex{display:flex}.justify-content-end{justify-content:flex-end}');
            ventana.document.write('.align-middle{vertical-align:middle}');
            ventana.document.write('.font-weight-bold{font-weight:700}');
            ventana.document.write('.bg-light{background:#f8f9fa}');
            ventana.document.write('.table-active td{background:#f8f9fa}');
            ventana.document.write('.form-control{display:none}');
            ventana.document.write('</style>');
            ventana.document.write('</head><body>');
            ventana.document.write('<h4 style="margin-bottom:1rem">Recaudaciones</h4>');
            ventana.document.write(contenido);
            ventana.document.write('</body></html>');
            ventana.document.close();
            ventana.focus();
            setTimeout(function() { ventana.print(); ventana.close(); }, 500);
        }
    </script>
@endpush
