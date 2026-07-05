<div class="container-fluid px-0">
    <style>
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: 2px solid transparent;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            margin-bottom: -2px;
            font-weight: 500;
            transition: all 0.2s ease;
            color: #495057;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            background-color: #f8f9fa;
        }

        .nav-tabs .nav-link.active {
            border-left: 2px solid #adb5bd;
            border-right: 2px solid #adb5bd;
            border-top: 3px solid #17a2b8;
            border-bottom-color: #fff;
            background-color: #fff;
            font-weight: 600;
            color: #495057;
        }

        html.dark-theme .nav-tabs {
            border-bottom-color: rgba(255, 255, 255, 0.15);
        }

        html.dark-theme .nav-tabs .nav-link {
            color: rgba(255, 255, 255, 0.65);
        }

        html.dark-theme .nav-tabs .nav-link:hover {
            border-color: rgba(255, 255, 255, 0.1) rgba(255, 255, 255, 0.1) rgba(255, 255, 255, 0.15);
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        html.dark-theme .nav-tabs .nav-link.active {
            border-left-color: rgba(255, 255, 255, 0.2);
            border-right-color: rgba(255, 255, 255, 0.2);
            border-top-color: #5bc0de;
            border-bottom-color: transparent;
            background-color: transparent;
            color: #fff;
        }
    </style>
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
                <div class="col d-flex align-items-end flex-wrap" style="gap:8px;">
                    @if($fecha)
                        <div>
                            <label class="small mb-1">Fecha</label>
                            <input type="date" class="form-control form-control-sm" wire:model="fecha" wire:change="$refresh">
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm" wire:click="$set('fecha', null)" title="Cambiar a filtro por mes/año">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @else
                        <div>
                            <label class="small mb-1">Mes</label>
                            <select class="form-control form-control-sm" wire:model="filtroMes" wire:change="$refresh">
                                @php $meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre']; @endphp
                                @foreach($meses as $num => $nombre)
                                    <option value="{{ $num }}">{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="small mb-1">Año</label>
                            <select class="form-control form-control-sm" wire:model="filtroAno" wire:change="$refresh">
                                @foreach($anosRegistrados as $ano)
                                    <option value="{{ $ano }}">{{ $ano }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button class="btn btn-outline-secondary btn-sm" wire:click="$set('fecha', '{{ date('Y-m-d') }}')" title="Cambiar a filtro por fecha específica">
                                <i class="fas fa-calendar-day"></i>
                            </button>
                        </div>
                    @endif
                    <div>
                        <label class="small mb-1">Buscar</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" wire:model.debounce.300ms="search" placeholder="Documento o monto..." style="min-width:180px;">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" wire:click="$set('search', '')" title="Limpiar búsqueda">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
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
                <ul class="nav nav-tabs mb-0" id="recaudacionesTab" role="tablist">
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
                <hr class="mt-0 mb-3" style="border-top: 1px solid #adb5bd; margin-top: 0 !important;">
                @php $primerActivo = true; @endphp
                <div class="tab-content p-3" id="recaudacionesTabContent">
                    @foreach($grupos as $key => $grupo)
                        @if($grupo['total_efectivo'] + $grupo['total_cheque'] + $grupo['total_transferencia'] + $grupo['total_pos'] > 0)
                            <div class="tab-pane fade {{ $primerActivo ? 'show active' : '' }}"
                                id="content-{{ \Illuminate\Support\Str::slug($key) }}" role="tabpanel"
                                aria-labelledby="tab-{{ \Illuminate\Support\Str::slug($key) }}">
                                @php $primerActivo = false; @endphp

                                @foreach($grupo['fechas'] as $fechaKey => $fecha)
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white py-1 px-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="far fa-calendar-alt mr-1"></i> {{ $fechaKey !== 'sin-fecha' ? \Carbon\Carbon::parse($fechaKey)->format('d/m/Y') : 'Sin fecha' }}</span>
                                                <span>Total del día: $ {{ number_format($fecha['total_efectivo'] + $fecha['total_cheque'] + $fecha['total_transferencia'] + $fecha['total_pos'], 2, ',', '.') }}</span>
                                            </div>
                                        </div>
                                        <div class="card-body p-2">
                                            @foreach($fecha['distribuciones'] as $distKey => $distribucion)
                                                @if(!empty($distribucion['items']))
                                                    <table class="table table-sm table-bordered mt-3 mb-2">
                                                        <thead>
                                                            <tr class="bg-light">
                                                                <th colspan="5" class="text-center py-1 font-weight-bold">
                                                                    {{ $distribucion['concepto'] }}
                                                                </th>
                                                            </tr>
                                                            <tr class="thead-light">
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
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
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
            ventana.document.write('.card{margin-bottom:1rem;border:1px solid #dee2e6}');
            ventana.document.write('.card-header,.table tr{page-break-inside:avoid}');
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
