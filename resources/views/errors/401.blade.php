@extends('layouts.minimal')

@section('title', 'Acceso No Autorizado')

@push('meta')
<meta http-equiv="refresh" content="3;url={{ route('login') }}">
@endpush

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-lock"></i> Acceso No Autorizado</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4"><i class="fas fa-user-times fa-4x text-danger"></i></div>
                    <h5 class="card-title">Debe iniciar sesión para continuar</h5>
                    <p class="card-text">Será redirigido automáticamente al inicio de sesión.</p>
                    <div class="mt-4">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                        </a>
                    </div>
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