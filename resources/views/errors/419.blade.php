@extends('layouts.minimal')

@section('title', 'Sesión Expirada')

@push('meta')
<meta http-equiv="refresh" content="3;url={{ route('login') }}">
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-info shadow">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-clock"></i> Sesión Expirada</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4"><i class="fas fa-history fa-4x text-info"></i></div>
                    <h5 class="card-title">La sesión ha expirado</h5>
                    <p class="card-text">
                        Debido a la inactividad, su sesión ha expirado por razones de seguridad.
                        Será redirigido automáticamente al inicio de sesión.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                        </a>
                    </div>
                    <p class="text-muted mt-3 mb-0"><small>Redirigiendo en unos segundos...</small></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    setTimeout(function () {
        window.location.href = document.querySelector('meta[name="login-url"]').getAttribute('content');
    }, 1500);
</script>
@endpush