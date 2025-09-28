@extends('layouts.app')

@section('content')
    <div class="container">
        <ul class="nav nav-pills" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ request()->routeIs('tesoreria.armas.porte') ? 'active' : '' }}" href="{{ route('tesoreria.armas.porte') }}">Porte de Armas</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ request()->routeIs('tesoreria.armas.tenencia') ? 'active' : '' }}" href="{{ route('tesoreria.armas.tenencia') }}">Tenencia de Armas (T.H.A.T.A.)</a>
            </li>
        </ul>
        <hr class="mt-0 mb-1">
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" role="tabpanel">
                @yield('content_armas')
            </div>
        </div>
    </div>
@endsection
