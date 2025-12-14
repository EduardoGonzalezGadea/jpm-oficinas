@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-0">
            <ul class="nav nav-pills flex-shrink-0 mb-0" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ request()->routeIs('tesoreria.armas.porte') ? 'active' : '' }}" href="{{ route('tesoreria.armas.porte') }}{{ request()->has('anio') ? '?anio=' . request('anio') : '' }}">Porte de Armas</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ request()->routeIs('tesoreria.armas.tenencia') ? 'active' : '' }}" href="{{ route('tesoreria.armas.tenencia') }}{{ request()->has('anio') ? '?anio=' . request('anio') : '' }}">Tenencia de Armas (T.H.A.T.A.)</a>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <label class="mb-0 mr-2 font-weight-bold">Año:</label>
                <select class="form-control form-control-sm" id="anioSelector" style="width: 120px;">
                    @php
                        $anioSeleccionado = (int) request('anio', date('Y'));
                    @endphp
                    @foreach($aniosDisponibles as $anio)
                        <option value="{{ $anio }}" {{ $anio == $anioSeleccionado ? 'selected' : '' }}>
                            {{ $anio }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <hr class="mt-0 mb-1">
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" role="tabpanel">
                @yield('content_armas')
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('anioSelector').addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('anio', this.value);
            window.location.href = url.toString();
        });
    </script>
    @endpush
@endsection
